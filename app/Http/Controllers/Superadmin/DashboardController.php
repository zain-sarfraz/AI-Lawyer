<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('superadmin.dashboard', [
            'stats' => [
                'firms' => Firm::query()->count(),
                'active_firms' => Firm::query()->where('status', 'active')->count(),
                'suspended_firms' => Firm::query()->where('status', 'suspended')->count(),
                'firm_admins' => User::query()
                    ->withoutGlobalScopes()
                    ->whereNotNull('firm_id')
                    ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Owner', 'Admin']))
                    ->count(),
            ],
            'recentFirms' => Firm::query()->latest('id')->limit(8)->get(),
        ]);
    }
}
