<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Domain\Firm\FirmContext;
use App\Domain\Lawyers\LawyerService;
use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Models\Jurisdiction;
use App\Models\Lawyer;
use App\Models\Service;
use App\Support\Phone\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LawyerController extends Controller
{
    public function __construct(private readonly LawyerService $lawyers) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('platform.lawyers.manage'), 403);

        $query = Lawyer::query()
            ->withoutGlobalScopes()
            ->with([
                'user' => fn ($q) => $q->withoutGlobalScopes(),
                'firm',
                'services' => fn ($q) => $q->withoutGlobalScopes(),
                'jurisdictions',
            ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->withoutGlobalScopes()
                       ->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('firm', function ($fq) use ($search) {
                    $fq->where('firm_name', 'like', "%{$search}%");
                });
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('status', 'active');
            } elseif ($status === 'inactive' || $status === 'suspended') {
                $query->where('status', '!=', 'active');
            }
        }

        if ($accountType = $request->input('account_type')) {
            $hasUserAccountType = Schema::hasColumn('users', 'account_type');
            $hasUserRole = Schema::hasColumn('users', 'role');
            $hasFirmAccountType = Schema::hasColumn('firms', 'account_type');

            if ($accountType === 'individual') {
                $query->where(function ($q) use ($hasUserAccountType, $hasUserRole, $hasFirmAccountType) {
                    $q->whereNull('firm_id');
                    if ($hasFirmAccountType) {
                        $q->orWhereHas('firm', fn ($fq) => $fq->where('account_type', 'individual'));
                    }
                    if ($hasUserAccountType || $hasUserRole) {
                        $q->orWhereHas('user', function ($uq) use ($hasUserAccountType, $hasUserRole) {
                            $uq->withoutGlobalScopes()->where(function ($sq) use ($hasUserAccountType, $hasUserRole) {
                                if ($hasUserAccountType) {
                                    $sq->where('account_type', 'individual');
                                }
                                if ($hasUserRole) {
                                    $hasUserAccountType
                                        ? $sq->orWhere('role', 'individual_lawyer')
                                        : $sq->where('role', 'individual_lawyer');
                                }
                            });
                        });
                    }
                });
            } elseif ($accountType === 'firm') {
                $query->whereNotNull('firm_id');
                if ($hasFirmAccountType) {
                    $query->whereHas('firm', fn ($fq) => $fq->where(fn ($fsq) => $fsq->where('account_type', 'firm')->orWhereNull('account_type')));
                }
                if ($hasUserAccountType || $hasUserRole) {
                    $query->where(function ($q) use ($hasUserAccountType, $hasUserRole) {
                        $q->whereNull('user_id')->orWhereHas('user', function ($uq) use ($hasUserAccountType, $hasUserRole) {
                            $uq->withoutGlobalScopes()->where(function ($sq) use ($hasUserAccountType, $hasUserRole) {
                                if ($hasUserAccountType) {
                                    $sq->where('account_type', 'firm')->orWhereNull('account_type');
                                }
                                if ($hasUserRole) {
                                    $method = $hasUserAccountType ? 'orWhere' : 'where';
                                    $sq->{$method}(fn ($rq) => $rq->whereNull('role')->orWhere('role', '!=', 'individual_lawyer'));
                                }
                            });
                        });
                    });
                }
            }
        }

        $lawyers = $query->orderBy('id', 'asc')->paginate(10)->withQueryString();

        $totalLawyers = Lawyer::withoutGlobalScopes()->count();
        $activeLawyers = Lawyer::withoutGlobalScopes()->where('status', 'active')->count();
        $inactiveLawyers = Lawyer::withoutGlobalScopes()->where('status', '!=', 'active')->count();

        $recentLawyers = Lawyer::withoutGlobalScopes()
            ->with(['user' => fn ($q) => $q->withoutGlobalScopes(), 'firm'])
            ->latest('id')
            ->limit(5)
            ->get();

        $firms = Firm::query()->lawFirms()->orderBy('firm_name')->get(['id', 'firm_name']);
        $servicesByFirm = Service::query()
            ->withoutGlobalScopes()
            ->orderBy('name')
            ->get(['id', 'firm_id', 'name'])
            ->groupBy('firm_id')
            ->map(fn ($rows) => $rows->map(fn (Service $s) => [
                'id' => (int) $s->id,
                'name' => $s->name,
            ])->values()->all())
            ->all();

        return view('superadmin.lawyers.index', [
            'lawyers' => $lawyers,
            'totalLawyers' => $totalLawyers,
            'activeLawyers' => $activeLawyers,
            'inactiveLawyers' => $inactiveLawyers,
            'recentLawyers' => $recentLawyers,
            'firms' => $firms,
            'servicesByFirm' => $servicesByFirm,
            'formLawyer' => new Lawyer(['status' => 'active']),
            'activeJurisdictions' => $this->activeJurisdictionsByCountry(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('platform.lawyers.manage'), 403);

        try {
            $data = $this->validatedLawyer($request);
            $phone = $this->normalizedPhone($data['phone_country'], $data['phone_local'] ?? null);
            $jurisdictionIds = $this->validatedJurisdictionIds($data['jurisdiction_ids'] ?? []);
            $firmId = (int) $data['firm_id'];
            $this->assertServicesBelongToFirm($firmId, $data['service_ids'] ?? []);
        } catch (ValidationException $e) {
            throw $e->redirectTo(route('superadmin.lawyers.index', ['open' => 'create-lawyer']));
        }

        app(FirmContext::class)->run($firmId, function () use ($data, $phone, $jurisdictionIds, $firmId) {
            $firm = Firm::query()->withoutGlobalScopes()->find($firmId);
            $firmTimezone = $firm?->resolvedTimezone() ?? Firm::DEFAULT_TIMEZONE;

            $this->lawyers->create(
                [
                    'title' => $data['title'] ?? null,
                    'country' => $data['phone_country'],
                    'phone' => $phone,
                    'experience' => $data['experience'] ?? null,
                    'bio' => $data['bio'] ?? null,
                    'timezone' => !empty($data['timezone']) ? $data['timezone'] : $firmTimezone,
                    'status' => $data['status'],
                    'specialties' => [],
                ],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => !empty($data['password']) ? $data['password'] : '11223344',
                ],
                $data['service_ids'] ?? [],
                $jurisdictionIds
            );
        });

        return redirect()->route('superadmin.lawyers.index')->with('success', 'Lawyer created.');
    }

    public function update(Request $request, int $lawyer): RedirectResponse
    {
        abort_unless(auth()->user()?->can('platform.lawyers.manage'), 403);

        $model = Lawyer::query()->withoutGlobalScopes()->with('user')->findOrFail($lawyer);
        $data = $this->validatedLawyer($request, $model);
        $phone = $this->normalizedPhone($data['phone_country'], $data['phone_local'] ?? null);
        if ($model->firm_id !== null) {
            $this->assertServicesBelongToFirm((int) $model->firm_id, $data['service_ids'] ?? []);
        }

        app(FirmContext::class)->run($model->firm_id !== null ? (int) $model->firm_id : null, function () use ($model, $data, $phone) {
            if ($model->user) {
                $userUpdates = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                ];
                if (! empty($data['password'])) {
                    $userUpdates['password'] = $data['password'];
                }
                $model->user->update($userUpdates);
            }

            $firm = $model->firm_id !== null ? Firm::query()->withoutGlobalScopes()->find($model->firm_id) : null;
            $tz = !empty($data['timezone']) ? $data['timezone'] : ($model->timezone ?? $firm?->resolvedTimezone() ?? Firm::DEFAULT_TIMEZONE);

            $this->lawyers->update($model, [
                'title' => $data['title'] ?? null,
                'country' => $data['phone_country'],
                'phone' => $phone,
                'experience' => $data['experience'] ?? null,
                'bio' => $data['bio'] ?? null,
                'timezone' => $tz,
                'status' => $data['status'],
            ], $model->firm_id !== null ? ($data['service_ids'] ?? []) : [], $this->validatedJurisdictionIds($data['jurisdiction_ids'] ?? []));
        });

        return redirect()->route('superadmin.lawyers.index')->with('success', 'Lawyer updated.');
    }

    public function destroy(int $lawyer): RedirectResponse
    {
        abort_unless(auth()->user()?->can('platform.lawyers.manage'), 403);

        $model = Lawyer::query()->withoutGlobalScopes()->findOrFail($lawyer);

        $message = app(FirmContext::class)->run($model->firm_id !== null ? (int) $model->firm_id : null, function () use ($model) {
            return $this->lawyers->delete($model);
        });

        return redirect()->route('superadmin.lawyers.index')->with('success', $message);
    }

    private function validatedLawyer(Request $request, ?Lawyer $lawyer = null): array
    {
        return $request->validate([
            'firm_id' => [$lawyer ? 'nullable' : 'required', 'integer', 'exists:firms,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')
                    ->where(function ($q) use ($request, $lawyer) {
                        $rawFirmId = $request->input('firm_id') ?: $lawyer?->firm_id;

                        return $rawFirmId !== null && $rawFirmId !== ''
                            ? $q->where('firm_id', (int) $rawFirmId)
                            : $q->whereNull('firm_id');
                    })
                    ->ignore($lawyer?->user_id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'phone_country' => ['required', 'string', Rule::in(['PK', 'GB', 'US', 'CA', 'AU'])],
            'phone_local' => ['nullable', 'string', 'max:32'],
            'jurisdiction_ids' => ['required', 'array', 'min:1'],
            'jurisdiction_ids.*' => ['integer', 'distinct', 'exists:jurisdictions,id'],
            'experience' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'string'],
            'service_ids' => ['array'],
            'service_ids.*' => ['integer'],
        ], [
            'firm_id.required' => 'Select a firm for this lawyer.',
            'jurisdiction_ids.required' => 'Select at least one practice jurisdiction checkbox.',
            'phone_country.required' => 'Select a phone dial country (PK / GB / US / CA / AU).',
            'email.unique' => 'This email is already used in this firm.',
        ]);
    }

    /** @param  list<int|string>  $serviceIds */
    private function assertServicesBelongToFirm(int $firmId, array $serviceIds): void
    {
        $serviceIds = array_values(array_unique(array_map('intval', $serviceIds)));
        if ($serviceIds === []) {
            return;
        }

        $allowed = Service::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firmId)
            ->whereIn('id', $serviceIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($allowed) !== count($serviceIds)) {
            throw ValidationException::withMessages([
                'service_ids' => 'One or more services do not belong to the selected firm.',
            ]);
        }
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
