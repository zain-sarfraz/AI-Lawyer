<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Legal\Contracts\LegalDataProvider;
use App\Domain\Legal\LegalDataProviderRegistry;
use App\Http\Controllers\Controller;
use App\Enums\CaseDisposition;
use App\Models\CaseLawRecord;
use App\Models\Court;
use App\Models\Jurisdiction;
use App\Models\Lawyer;
use App\Support\Legal\CaseResearchFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Cases — legal research over an external case-law provider (CourtListener).
 *
 * Distinct from "Cases Management", which tracks the firm's own matters.
 */
class CaseResearchController extends Controller implements HasMiddleware
{
    /** Country codes offered in the filter — unsupported ones are marked as such. */
    private const COUNTRY_LABELS = [
        'US' => 'United States',
        'GB' => 'United Kingdom',
        'CA' => 'Canada',
        'AU' => 'Australia',
        'IN' => 'India',
        'PK' => 'Pakistan',
        'NG' => 'Nigeria',
        'EG' => 'Egypt',
        'SA' => 'Saudi Arabia',
        'TR' => 'Türkiye',
        'DE' => 'Germany',
        'FR' => 'France',
        'AE' => 'United Arab Emirates',
        'MY' => 'Malaysia',
        'BD' => 'Bangladesh',
        'ID' => 'Indonesia',
        'PH' => 'Philippines',
        'ZA' => 'South Africa',
        'KE' => 'Kenya',
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:cases.view'),
        ];
    }

    /* ----------------------------------------------------------
     |  Jurisdiction case-law table (DB only — no live scrape on load)
     | ---------------------------------------------------------- */

    public function index(Request $request): View
    {
        $lawyer = $this->currentLawyer($request);
        $jurisdictionIds = $lawyer
            ? $lawyer->jurisdictions()->pluck('jurisdictions.id')->all()
            : Jurisdiction::query()->active()->pluck('id')->all();

        $query = CaseLawRecord::query()
            ->with(['jurisdiction', 'court'])
            ->when(
                $jurisdictionIds !== [],
                fn ($q) => $q->whereIn('jurisdiction_id', $jurisdictionIds),
                fn ($q) => $q->whereRaw('1 = 0')
            );

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('citation', 'like', "%{$search}%")
                    ->orWhere('neutral_citation', 'like', "%{$search}%")
                    ->orWhere('case_number', 'like', "%{$search}%")
                    ->orWhere('keywords_json', 'like', "%{$search}%");
            });
        }

        if ($jid = $request->integer('jurisdiction_id')) {
            if ($jurisdictionIds !== [] && ! in_array($jid, $jurisdictionIds, true)) {
                abort(403);
            }
            $query->where('jurisdiction_id', $jid);
        }

        if ($cid = $request->integer('court_id')) {
            $query->where('court_id', $cid);
        }

        if ($year = $request->integer('year')) {
            $query->whereYear('decision_date', $year);
        }

        if ($caseType = trim((string) $request->input('case_type', ''))) {
            $query->where('case_type', $caseType);
        }

        if ($request->filled('disposition') && ($disposition = CaseDisposition::tryNormalize($request->input('disposition')))) {
            $query->where('disposition', $disposition->value);
        }

        // Never surface demo or incomplete source rows.
        $query->realOnly();

        $cases = $query
            ->orderByDesc('decision_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $jurisdictions = Jurisdiction::query()
            ->whereIn('id', $jurisdictionIds ?: [0])
            ->orderBy('name')
            ->get();

        $courts = Court::query()
            ->whereIn('jurisdiction_id', $jurisdictionIds ?: [0])
            ->orderBy('name')
            ->get();

        $caseTypes = ['criminal', 'civil', 'writ', 'appeal', 'family', 'tax', 'other'];

        $years = CaseLawRecord::query()
            ->realOnly()
            ->whereIn('jurisdiction_id', $jurisdictionIds ?: [0])
            ->whereNotNull('decision_date')
            ->selectRaw('YEAR(decision_date) as y')
            ->distinct()
            ->orderByDesc('y')
            ->pluck('y');

        if ($lawyer) {
            $lawyer->load('jurisdictions');
        }

        $fetchMeta = Jurisdiction::query()
            ->whereIn('id', $jurisdictionIds ?: [0])
            ->get()
            ->map(fn (Jurisdiction $j) => [
                'name' => $j->name,
                'status' => $j->last_case_fetch_status,
                'at' => optional($j->last_case_fetch_at)?->toDateTimeString(),
                'message' => $j->last_case_fetch_message,
            ])
            ->all();

        return view('admin.case-research.index', [
            'lawyer' => $lawyer,
            'cases' => $cases,
            'jurisdictions' => $jurisdictions,
            'courts' => $courts,
            'caseTypes' => $caseTypes,
            'years' => $years,
            'fetchMeta' => $fetchMeta,
            'filters' => [
                'q' => $search,
                'jurisdiction_id' => $request->input('jurisdiction_id'),
                'court_id' => $request->input('court_id'),
                'year' => $request->input('year'),
                'case_type' => $request->input('case_type'),
                'disposition' => $request->input('disposition'),
            ],
            'dispositions' => CaseDisposition::cases(),
        ]);
    }

    public function search(Request $request): RedirectResponse
    {
        return redirect()->route('case-research.index', $request->query());
    }

    public function show(CaseLawRecord $caseLaw): View
    {
        $lawyer = $this->currentLawyer(request());
        $allowed = $lawyer
            ? $lawyer->jurisdictions()->pluck('jurisdictions.id')->all()
            : Jurisdiction::query()->active()->pluck('id')->all();

        if (! in_array((int) $caseLaw->jurisdiction_id, array_map('intval', $allowed), true)) {
            abort(403);
        }

        if (! $caseLaw->isRealForUi()) {
            abort(404);
        }

        $caseLaw->load(['jurisdiction', 'court']);

        $counsel = array_values(array_filter($caseLaw->counsel_json ?? [], static fn ($row) => is_array($row) && filled($row['name'] ?? null)));
        $matchedFirmLawyers = $this->matchCounselToFirmLawyers($counsel);

        return view('admin.case-research.show', [
            'record' => $caseLaw,
            'lawyer' => $lawyer,
            'counsel' => $counsel,
            'matchedFirmLawyers' => $matchedFirmLawyers,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $counsel
     * @return array<string, \App\Models\Lawyer>
     */
    private function matchCounselToFirmLawyers(array $counsel): array
    {
        if ($counsel === []) {
            return [];
        }

        $lawyers = \App\Models\Lawyer::query()->with('user')->get();
        $matched = [];

        foreach ($counsel as $row) {
            $name = mb_strtolower(trim((string) ($row['name'] ?? '')));
            if ($name === '') {
                continue;
            }
            foreach ($lawyers as $firmLawyer) {
                $firmName = mb_strtolower(trim((string) ($firmLawyer->user?->name ?? '')));
                if ($firmName === '') {
                    continue;
                }
                if ($firmName === $name || str_contains($name, $firmName) || str_contains($firmName, $name)) {
                    $matched[$name] = $firmLawyer;
                    break;
                }
            }
        }

        return $matched;
    }

    /* ----------------------------------------------------------
     |  AJAX endpoint for filter / pagination updates
     | ---------------------------------------------------------- */

    public function apiSearch(LegalDataProviderRegistry $registry, Request $request): JsonResponse
    {
        $provider = $this->provider($registry, $request);
        $filters  = $this->filters($registry, $request);

        return response()->json(
            $this->applyOutcomeFilter($provider->search($filters->toArray()), $filters)
                + ['filters' => $filters->toView()]
        );
    }

    /* ----------------------------------------------------------
     |  Single case detail (server-side proxy; token never leaves PHP)
     | ---------------------------------------------------------- */

    public function apiCase(LegalDataProviderRegistry $registry, Request $request, string $caseId): JsonResponse
    {
        if (! preg_match('/^\d{1,12}$/', $caseId)) {
            return response()->json(['ok' => false, 'message' => 'That case reference is not valid.'], 422);
        }

        $provider = $this->provider($registry, $request);
        $case     = $provider->getCase($caseId);

        if (is_array($case) && ($case['unavailable'] ?? false)) {
            return response()->json([
                'ok'           => false,
                'reason'       => 'rate_limited',
                'message'      => 'CourtListener is rate limited. Retry in '.$case['retry_after'].'s.',
                'retry_after'  => $case['retry_after'],
            ], 429);
        }

        if (! is_array($case)) {
            return response()->json([
                'ok'      => false,
                'reason'  => 'not_found',
                'message' => 'The source record could not be retrieved. It may have been withdrawn or is not exposed by the provider.',
            ], 404);
        }

        return response()->json(['ok' => true, 'case' => $case]);
    }

    /* ----------------------------------------------------------
     |  Court directory for the searchable court filter
     | ---------------------------------------------------------- */

    public function apiCourts(LegalDataProviderRegistry $registry, Request $request): JsonResponse
    {
        $provider = $this->provider($registry, $request);

        return response()->json([
            'ok'      => true,
            'courts'  => $provider->courts(),
            'message' => $provider->isConfigured() ? null : 'Connect the CourtListener API token to load the court directory.',
        ]);
    }

    /* ----------------------------------------------------------
     |  Verified coverage probe: one category per call so the
     |  provider's request quota is never exhausted by the landing page.
     | ---------------------------------------------------------- */

    public function apiCoverage(LegalDataProviderRegistry $registry, Request $request): JsonResponse
    {
        $provider = $this->provider($registry, $request);
        $category = (string) $request->input('category', '');

        if (! array_key_exists($category, $provider->categories())) {
            return response()->json(['ok' => false, 'message' => 'Unknown category.'], 422);
        }

        $window = CaseResearchFilters::fromRequest(
            Request::create('/', 'GET', ['date_range' => '2y']),
            $registry->supportedCountries(),
        );

        $count = $provider->countCategory($category, $window->dateFrom, $window->dateTo);

        return response()->json([
            'ok'        => $count !== null,
            'category'  => $category,
            'count'     => $count,
            'verified'  => $count !== null,
        ]);
    }

    /* ----------------------------------------------------------
     |  Case types for the searchable selector
     | ---------------------------------------------------------- */

    public function apiCaseTypes(LegalDataProviderRegistry $registry, Request $request): JsonResponse
    {
        $provider = $this->provider($registry, $request);

        return response()->json([
            'ok'         => true,
            'case_types' => $provider->caseTypes((string) $request->input('category', '')),
        ]);
    }

    /* ----------------------------------------------------------
     |  Helpers
     | ---------------------------------------------------------- */

    private function provider(LegalDataProviderRegistry $registry, Request $request): LegalDataProvider
    {
        $country = strtoupper(trim((string) $request->input('country', '')));

        return $registry->forCountry($country) ?? $registry->default();
    }

    /**
     * Country selector options. Supported countries are selectable; the lawyer's
     * own signup country is still offered (clearly marked) so the module can
     * explain that no data source covers it instead of returning fake records.
     *
     * @return list<array<string, mixed>>
     */
    private function countryOptions(array $supported, ?string $signupCountry): array
    {
        $codes = $supported;

        if ($signupCountry !== null) {
            $codes[] = $signupCountry;
        }

        $codes = collect($codes)
            ->map(fn (string $code) => strtoupper(trim($code)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return array_map(fn (string $code) => [
            'value'     => $code,
            'label'     => self::COUNTRY_LABELS[$code] ?? $code,
            'supported' => in_array($code, $supported, true),
        ], $codes);
    }

    private function filters(LegalDataProviderRegistry $registry, Request $request): CaseResearchFilters
    {
        return CaseResearchFilters::fromRequest(
            $request,
            $registry->supportedCountries(),
            $this->signupCountry($request),
        );
    }

    /**
     * Signup country of the authenticated lawyer, when stored. Used only as a
     * default filter and only if a provider covers it.
     */
    private function signupCountry(Request $request): ?string
    {
        $user = $request->user();

        return $user?->lawyer?->country ?: null;
    }

    private function currentLawyer(Request $request): ?Lawyer
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return Lawyer::query()->where('user_id', $user->id)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function viewData(LegalDataProvider $provider, CaseResearchFilters $filters, array $data, ?string $signupCountry = null): array
    {
        return [
            'initialData'   => $data,
            'filters'       => $filters->toView(),
            'categories'    => $provider->categories(),
            'caseTypes'     => $provider->caseTypes($filters->category),
            'configured'    => $provider->isConfigured(),
            'provider'      => $provider->providerName(),
            'countries'     => $provider->supportedCountries(),
            'countryOptions' => $this->countryOptions($provider->supportedCountries(), $signupCountry),
            'signupCountry' => $signupCountry,
            'dateRanges'    => CaseResearchFilters::DATE_RANGE_LABELS,
        ];
    }

    /**
     * Apply the outcome filter to records the provider already returned.
     * Filtering happens on verified classifications only; nothing is invented
     * to satisfy a won/lost selection.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyOutcomeFilter(array $data, CaseResearchFilters $filters): array
    {
        if ($filters->outcome === '' || ! isset($data['results'])) {
            return $data;
        }

        $matched = array_values(array_filter(
            $data['results'],
            fn (array $record) => ($record['outcome_class'] ?? 'unknown') === $filters->outcome,
        ));

        $data['results']         = $matched;
        $data['outcome_filter']  = $filters->outcome;
        $data['hidden_by_outcome'] = max(0, (int) ($data['stats']['analyzed'] ?? 0) - count($matched));

        if ($matched === []) {
            $data['reason']  = 'empty';
            $data['message'] = $filters->outcome === 'unknown'
                ? 'No records in this page are classified as Unknown.'
                : 'No record in this page has a verifiable "'.ucfirst($filters->outcome).'" outcome. '.
                  'CourtListener does not publish party-relative judgments, so won/lost cannot be claimed.';
        }

        return $data;
    }
}
