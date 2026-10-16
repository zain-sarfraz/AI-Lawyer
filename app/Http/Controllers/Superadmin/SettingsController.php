<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('superadmin.settings.edit', [
            'stats' => [
                'firms' => Firm::query()->count(),
                'active_firms' => Firm::query()->where('status', 'active')->count(),
                'admins' => User::query()
                    ->withoutGlobalScopes()
                    ->whereNotNull('firm_id')
                    ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Owner', 'Admin']))
                    ->count(),
            ],
            'appName' => config('app.name'),
            'appUrl' => config('app.url'),
            'timezone' => config('app.timezone'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Platform settings are env-driven for now; keep the form for future persistence.
        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        return redirect()
            ->route('superadmin.settings.edit')
            ->with('success', 'Platform settings noted. App name and timezone are controlled via .env.');
    }
}
