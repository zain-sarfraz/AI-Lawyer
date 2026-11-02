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
            'activeJurisdictions' => $this->activeJurisdictionsGrouped(),
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

        $data = $this->validatedLawyer($request);
        $phone = $this->normalizedPhone($data['phone_country'], $data['phone_local'] ?? null);

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
            $data['jurisdiction_ids'] ?? []
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
            ->whereIn('jurisdiction_id', $jurisdictionIds ?: [0])
            ->whereNotNull('case_type')
            ->distinct()
            ->orderBy('case_type')
            ->pluck('case_type');

        $years = CaseLawRecord::query()
            ->whereIn('jurisdiction_id', $jurisdictionIds ?: [0])
            ->whereNotNull('decision_date')
            ->selectRaw('YEAR(decision_date) as y')
            ->distinct()
            ->orderByDesc('y')
            ->pluck('y');

        return view('admin.lawyers.show', [
            'lawyer' => $lawyer,
            'tab' => 'cases',
            'cases' => $query->orderByDesc('decision_date')->orderByDesc('id')->paginate(15)->withQueryString(),
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
        ]);
    }

    public function caseLawShow(Lawyer $lawyer, CaseLawRecord $caseLaw): View
    {
        $this->authorize('view', $lawyer);
        $this->authorize('viewCaseLaw', [$lawyer, $caseLaw]);

        $lawyer->load('jurisdictions');
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
        ], $data['service_ids'] ?? [], $data['jurisdiction_ids'] ?? []);

        return redirect()->route('lawyers.index')->with('success', 'Lawyer updated.');
    }

    private function validatedLawyer(Request $request, ?Lawyer $lawyer = null): array
    {
        $activeIds = Jurisdiction::query()->active()->pluck('id')->all();

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                $lawyer
                    ? Rule::unique('users', 'email')->ignore($lawyer->user_id)
                    : Rule::unique('users', 'email'),
            ],
            'password' => [$lawyer ? 'nullable' : 'nullable', 'string', 'min:8'],
            'phone_country' => ['required', 'string', Rule::in(['PK', 'GB'])],
            'phone_local' => ['nullable', 'string', 'max:32'],
            'experience' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'string'],
            'service_ids' => ['array'],
            'service_ids.*' => ['exists:services,id'],
            'jurisdiction_ids' => ['required', 'array', 'min:1'],
            'jurisdiction_ids.*' => ['integer', Rule::in($activeIds)],
        ]);
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

    /** @return array<string, \Illuminate\Support\Collection<int, Jurisdiction>> */
    private function activeJurisdictionsGrouped(): array
    {
        return Jurisdiction::query()
            ->active()
            ->orderBy('country_code')
            ->orderBy('name')
            ->get()
            ->groupBy('country_code')
            ->all();
    }
}
