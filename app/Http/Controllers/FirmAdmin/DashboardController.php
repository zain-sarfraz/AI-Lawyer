<?php

declare(strict_types=1);

namespace App\Http\Controllers\FirmAdmin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Models\Jurisdiction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->loadMissing('firm', 'lawyer');
        $firm = Firm::current();

        $lawyerUsers = User::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Lawyer', 'FirmAdmin']))
            ->with(['roles', 'lawyer'])
            ->get();

        $totalLawyers = $lawyerUsers->count();
        $activeLawyers = $lawyerUsers->filter(fn (User $u) => $u->status === UserStatus::Active)->count();
        $inactiveLawyers = $lawyerUsers->filter(fn (User $u) => $u->status !== UserStatus::Active)->count();
        $withPhone = $lawyerUsers->filter(fn (User $u) => filled($u->lawyer?->phone))->count();

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

        $lawyers = $query->paginate(10)->withQueryString();

        $hour = (int) now()->format('G');
        $partOfDay = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        return view('firm-admin.dashboard', [
            'greeting' => "{$partOfDay}, {$user->name}.",
            'firm' => $firm,
            'stats' => [
                'total' => $totalLawyers,
                'active' => $activeLawyers,
                'with_phone' => $withPhone,
                'inactive' => $inactiveLawyers,
            ],
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
}
