<?php

declare(strict_types=1);

namespace App\Http\Controllers\FirmAdmin;

use App\Domain\Users\FirmUserService;
use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Models\Jurisdiction;
use App\Models\User;
use App\Support\Phone\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LawyerController extends Controller
{
    public function __construct(private readonly FirmUserService $users) {}

    public function index(Request $request): View
    {
        $firm = Firm::current();

        $query = User::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Lawyer', 'FirmAdmin']))
            ->with([
                'roles',
                'lawyer' => fn ($q) => $q->withCount('legalCases')->with('jurisdictions'),
            ]);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('lawyer', fn ($lawyer) => $lawyer->where('phone', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            if (in_array($status, ['active', 'inactive', 'suspended'], true)) {
                $query->where('status', $status);
            }
        }

        $sort = $request->query('sort', 'newest');
        match ($sort) {
            'name' => $query->orderBy('name'),
            'email' => $query->orderBy('email'),
            'oldest' => $query->orderBy('id'),
            default => $query->latest('id'),
        };

        $lawyers = $query->paginate(15)->withQueryString();

        return view('firm-admin.lawyers.index', [
            'firm' => $firm,
            'lawyers' => $lawyers,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'sort' => $sort,
            ],
            'canAddLawyer' => $firm->canAddLawyer(),
            'activeJurisdictions' => $this->activeJurisdictionsByCountry(),
        ]);
    }

    public function show(User $user): View
    {
        $this->assertFirmMember($user);

        $user->load(['roles', 'lawyer.legalCases.client', 'lawyer.jurisdictions', 'firm']);

        return view('firm-admin.lawyers.show', [
            'member' => $user,
            'firm' => Firm::current(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $firm = Firm::current();

        if (! $firm->canAddLawyer()) {
            throw ValidationException::withMessages([
                'email' => 'Your plan does not allow additional lawyer seats.',
            ])->redirectTo(route('firm.lawyers.index', ['open' => 'add-lawyer']));
        }

        try {
            $data = $this->validatedMember($request);
            $data['phone'] = $this->normalizedPhone($data['phone_country'], $data['phone_local'] ?? null);
            $data['country'] = $data['phone_country'];
            $data['jurisdiction_ids'] = $this->validatedJurisdictionIds($data['jurisdiction_ids'] ?? []);
            unset($data['phone_local'], $data['phone_country']);
        } catch (ValidationException $e) {
            throw $e->redirectTo(route('firm.lawyers.index', ['open' => 'add-lawyer']));
        }

        $user = $this->users->create($data);

        return redirect()
            ->route('firm.lawyers.index')
            ->with('success', $user->name.' was added to the firm and can sign in now.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->assertFirmMember($user);

        if ($user->isFirmAdmin() && $user->id !== $request->user()->id) {
            return back()->withErrors(['user' => 'Another firm admin account cannot be edited here.']);
        }

        $modalName = 'edit-firm-member-'.$user->id;

        try {
            $data = $this->validatedMember($request, $user);
            $data['phone'] = $this->normalizedPhone($data['phone_country'], $data['phone_local'] ?? null);
            $data['country'] = $data['phone_country'];
            $data['jurisdiction_ids'] = $this->validatedJurisdictionIds($data['jurisdiction_ids'] ?? []);
            unset($data['phone_local'], $data['phone_country']);
        } catch (ValidationException $e) {
            throw $e->redirectTo(route('firm.lawyers.index', ['open' => $modalName]));
        }

        if (! $user->isFirmAdmin()) {
            $data['role'] = 'Lawyer';
        }

        $this->users->update($user, $data);

        return redirect()
            ->route('firm.lawyers.index')
            ->with('success', 'Lawyer details updated.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $this->assertFirmMember($user);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['status' => 'You cannot deactivate your own firm admin account.']);
        }

        if ($user->isFirmAdmin()) {
            return back()->withErrors(['status' => 'Firm admin accounts cannot be deactivated from this screen.']);
        }

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $this->users->setStatus($user, $data['status']);

        $label = $data['status'] === 'active' ? 'reactivated' : 'deactivated';

        return back()->with('success', $user->name.' was '.$label.'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedMember(Request $request, ?User $user = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone_country' => ['required', 'string', Rule::in(['PK', 'GB', 'US', 'CA', 'AU'])],
            'phone_local' => ['required', 'string', 'max:32'],
            'jurisdiction_ids' => ['required', 'array', 'min:1'],
            'jurisdiction_ids.*' => ['integer', 'distinct', 'exists:jurisdictions,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(['Lawyer'])],
            'status' => ['nullable', 'in:active,inactive,suspended'],
        ];

        if ($user) {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['role'] = ['required', Rule::in(['Lawyer'])];
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        return $request->validate($rules, [
            'jurisdiction_ids.required' => 'Select at least one practice jurisdiction for case law.',
            'jurisdiction_ids.min' => 'Select at least one practice jurisdiction for case law.',
            'phone_country.required' => 'Select a phone dial country (PK / GB / US / CA / AU).',
            'phone_local.required' => 'Enter the phone number for the selected dial country.',
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

    private function assertFirmMember(User $user): void
    {
        $firm = Firm::current();
        $member = User::query()->withoutGlobalScopes()->findOrFail($user->id);

        if ((int) $member->firm_id !== (int) $firm->id) {
            abort(404);
        }

        if (! $member->hasAnyRole(['Lawyer', 'FirmAdmin'])) {
            abort(404);
        }
    }
}
