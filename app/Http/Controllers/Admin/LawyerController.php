<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Lawyers\LawyerService;
use App\Enums\CaseDisposition;
use App\Http\Controllers\Controller;
use App\Models\CaseLawRecord;
use App\Models\Court;
use App\Models\Jurisdiction;
use App\Models\Lawyer;
use App\Models\Service;
use App\Support\Phone\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LawyerController extends Controller implements HasMiddleware
{
    public function __construct(private readonly LawyerService $lawyers) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:lawyers.manage', except: ['show', 'relevantCases', 'caseLawShow']),
            new Middleware('permission:lawyers.manage|cases.view', only: ['show', 'relevantCases', 'caseLawShow']),
        ];
    }

    public function index(): View
    {
        $this->authorize('viewAny', Lawyer::class);

        return view('admin.lawyers.index', [
            'lawyers' => Lawyer::query()->with(['user', 'services', 'jurisdictions'])->paginate(25),
            'formLawyer' => new Lawyer(['status' => 'active']),
            'formServices' => Service::query()->orderBy('name')->get(),
            'activeJurisdictions' => $this->activeJurisdictionsByCountry(),
        ]);
    }

    public function create(): RedirectResponse
    {
        $this->authorize('create', Lawyer::class);

        return redirect()->route('lawyers.index', ['open' => 'create-lawyer']);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Lawyer::class);

        try {
            $data = $this->validatedLawyer($request);
            $phone = $this->normalizedPhone($data['phone_country'], $data['phone_local'] ?? null);
            $jurisdictionIds = $this->validatedJurisdictionIds($data['jurisdiction_ids'] ?? []);
        } catch (ValidationException $e) {
            throw $e->redirectTo(route('lawyers.index', ['open' => 'create-lawyer']));
        }

        $this->lawyers->create(
            [
                'title' => $data['title'] ?? null,
                'country' => $data['phone_country'],
                'phone' => $phone,
                'experience' => $data['experience'] ?? null,
                'bio' => $data['bio'] ?? null,
                'timezone' => $data['timezone'] ?? null,
                'status' => $data['status'],
                'specialties' => [],
            ],
            [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'] ?? 'password',
            ],
            $data['service_ids'] ?? [],
            $jurisdictionIds
        );

        return redirect()->route('lawyers.index')->with('success', 'Lawyer created.');
    }

    public function show(Lawyer $lawyer): View
    {
        $this->authorize('view', $lawyer);

        $lawyer->load(['user', 'services', 'jurisdictions']);

        return view('admin.lawyers.show', [
            'lawyer' => $lawyer,
            'tab' => 'profile',
        ]);
    }

    public function relevantCases(Request $request, Lawyer $lawyer): View
    {
        $this->authorize('view', $lawyer);
        $this->authorize('viewRelevantCases', $lawyer);

        $lawyer->load('jurisdictions');
        $jurisdictionIds = $lawyer->jurisdictions->pluck('id')->all();

        $query = CaseLawRecord::query()
            ->with(['jurisdiction', 'court'])
            ->forJurisdictions($jurisdictionIds)
            ->realOnly()
            ->latest('decision_date');

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('citation', 'like', "%{$search}%");
            });
        }

        if ($jid = $request->integer('jurisdiction_id')) {
            if (! in_array($jid, $jurisdictionIds, true)) {
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

        $courts = Court::query()
            ->whereIn('jurisdiction_id', $jurisdictionIds ?: [0])
            ->orderBy('name')
            ->get();

        $caseTypes = CaseLawRecord::query()
            ->realOnly()
            ->whereIn('jurisdiction_id', $jurisdictionIds ?: [0])
            ->whereNotNull('case_type')
            ->distinct()
            ->orderBy('case_type')
            ->pluck('case_type');

        $years = CaseLawRecord::query()
            ->realOnly()
            ->whereIn('jurisdiction_id', $jurisdictionIds ?: [0])
            ->whereNotNull('decision_date')
            ->selectRaw('YEAR(decision_date) as y')
            ->distinct()
            ->orderByDesc('y')
            ->pluck('y');

        $fetchMeta = $lawyer->jurisdictions
            ->map(fn (Jurisdiction $j) => [
                'name' => $j->name,
                'status' => $j->last_case_fetch_status,
                'at' => optional($j->last_case_fetch_at)?->toDateTimeString(),
                'message' => $j->last_case_fetch_message,
            ])
            ->all();

        return view('admin.lawyers.show', [
            'lawyer' => $lawyer,
            'tab' => 'cases',
            'cases' => $query->orderByDesc('decision_date')->orderByDesc('id')->paginate(50)->withQueryString(),
            'filters' => [
                'q' => $search,
                'jurisdiction_id' => $request->input('jurisdiction_id'),
                'court_id' => $request->input('court_id'),
                'year' => $request->input('year'),
                'case_type' => $request->input('case_type'),
                'disposition' => CaseDisposition::tryNormalize($request->input('disposition'))?->value,
            ],
            'courts' => $courts,
            'caseTypes' => $caseTypes,
            'years' => $years,
            'dispositions' => CaseDisposition::cases(),
            'fetchMeta' => $fetchMeta,
        ]);
    }

    public function caseLawShow(Lawyer $lawyer, CaseLawRecord $caseLaw): View
    {
        $this->authorize('view', $lawyer);
        $this->authorize('viewCaseLaw', [$lawyer, $caseLaw]);

        $lawyer->load('jurisdictions');
        if (! $caseLaw->isRealForUi()) {
            abort(404);
        }
        $caseLaw->load(['jurisdiction', 'court']);

        return view('admin.lawyers.case-law-show', [
            'lawyer' => $lawyer,
            'record' => $caseLaw,
        ]);
    }

    public function edit(Lawyer $lawyer): RedirectResponse
    {
        $this->authorize('update', $lawyer);

        return redirect()->route('lawyers.index', ['open' => 'edit-lawyer-'.$lawyer->id]);
    }

    public function destroy(Lawyer $lawyer): RedirectResponse
    {
        $this->authorize('delete', $lawyer);

        $message = $this->lawyers->delete($lawyer);

        return redirect()->route('lawyers.index')->with('success', $message);
    }

    public function update(Request $request, Lawyer $lawyer): RedirectResponse
    {
        $this->authorize('update', $lawyer);

        $data = $this->validatedLawyer($request, $lawyer);
        $phone = $this->normalizedPhone($data['phone_country'], $data['phone_local'] ?? null);

        if ($lawyer->user) {
            $userUpdates = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];
            if (! empty($data['password'])) {
                $userUpdates['password'] = $data['password'];
            }
            $lawyer->user->update($userUpdates);
        }

        $this->lawyers->update($lawyer, [
            'title' => $data['title'] ?? null,
            'country' => $data['phone_country'],
            'phone' => $phone,
            'experience' => $data['experience'] ?? null,
            'bio' => $data['bio'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'status' => $data['status'],
        ], $data['service_ids'] ?? [], $this->validatedJurisdictionIds($data['jurisdiction_ids'] ?? []));

        return redirect()->route('lawyers.index')->with('success', 'Lawyer updated.');
    }

    private function validatedLawyer(Request $request, ?Lawyer $lawyer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                $lawyer
                    ? Rule::unique('users', 'email')->ignore($lawyer->user_id)
                    : Rule::unique('users', 'email'),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'phone_country' => ['required', 'string', Rule::in(['PK', 'GB', 'US'])],
            'phone_local' => ['nullable', 'string', 'max:32'],
            'jurisdiction_ids' => ['required', 'array', 'min:1'],
            'jurisdiction_ids.*' => ['integer', 'distinct', 'exists:jurisdictions,id'],
            'experience' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'string'],
            'service_ids' => ['array'],
            'service_ids.*' => ['exists:services,id'],
        ], [
            'jurisdiction_ids.required' => 'Select at least one practice jurisdiction checkbox.',
            'jurisdiction_ids.min' => 'Select at least one practice jurisdiction checkbox.',
            'phone_country.required' => 'Select a phone dial country (PK / GB / US).',
            'email.unique' => 'This email is already registered.',
            'password.min' => 'Password must be at least 8 characters (or leave blank).',
        ]);
    }

    /** @return list<int> */
    private function validatedJurisdictionIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $allowed = Jurisdiction::query()
            ->active()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($allowed === [] || count($allowed) !== count($ids)) {
            throw ValidationException::withMessages([
                'jurisdiction_ids' => 'Select one or more active practice jurisdictions.',
            ]);
        }

        return $allowed;
    }

    /** @return array<string, list<array{id:int,code:string,name:string}>> */
    private function activeJurisdictionsByCountry(): array
    {
        $grouped = [];
        Jurisdiction::query()
            ->active()
            ->orderBy('country_code')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'country_code'])
            ->each(function (Jurisdiction $j) use (&$grouped) {
                $grouped[$j->country_code][] = [
                    'id' => (int) $j->id,
                    'code' => $j->code,
                    'name' => $j->name,
                ];
            });

        return $grouped;
    }

    private function normalizedPhone(string $country, ?string $local): ?string
    {
        try {
            $phone = PhoneNormalizer::normalize($country, $local);

            return $phone !== '' ? $phone : null;
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'phone_local' => $e->getMessage(),
            ]);
        }
    }

}
