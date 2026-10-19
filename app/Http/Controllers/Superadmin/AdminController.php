<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Domain\Platform\PlatformFirmService;
use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(private readonly PlatformFirmService $platform) {}

    public function index(): View
    {
        $admins = User::query()
            ->withoutGlobalScopes()
            ->whereNotNull('firm_id')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Owner', 'Admin']))
            ->with(['roles', 'firm'])
            ->latest('id')
            ->paginate(20);

        return view('superadmin.admins.index', compact('admins'));
    }

    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('superadmin.admins.index', array_filter([
            'open' => 'add-admin',
            'firm_id' => $request->query('firm_id'),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'firm_id' => ['required', 'integer', 'exists:firms,id'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'admin_role' => ['nullable', 'in:Owner,Admin'],
        ]);

        $firm = Firm::query()->findOrFail($data['firm_id']);

        $this->platform->addAdminToFirm($firm, $data);

        return redirect()
            ->route('superadmin.admins.index')
            ->with('success', 'Admin created for '.$firm->firm_name.'. They only see that firm\'s dashboard data.');
    }

    public function update(Request $request, int $admin): RedirectResponse
    {
        $user = User::query()
            ->withoutGlobalScopes()
            ->with('roles')
            ->findOrFail($admin);

        $data = $request->validate([
            'firm_id' => ['required', 'integer', 'exists:firms,id'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'admin_role' => ['nullable', 'in:Owner,Admin'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $this->platform->updateAdmin($user, $data);

        return redirect()
            ->route('superadmin.admins.index')
            ->with('success', 'Admin updated.');
    }

    public function destroy(int $admin): RedirectResponse
    {
        $user = User::query()
            ->withoutGlobalScopes()
            ->with('roles')
            ->findOrFail($admin);

        $name = $user->name;
        $this->platform->deleteAdmin($user);

        return redirect()
            ->route('superadmin.admins.index')
            ->with('success', $name.' removed from firm admins.');
    }
}
