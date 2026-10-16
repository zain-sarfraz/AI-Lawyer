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

class FirmController extends Controller
{
    public function __construct(private readonly PlatformFirmService $platform) {}

    public function index(): View
    {
        $firms = Firm::query()
            ->withCount('users')
            ->latest('id')
            ->paginate(20);

        return view('superadmin.firms.index', compact('firms'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('superadmin.firms.index', ['open' => 'create-firm']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'firm_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $firm = $this->platform->createFirm($data);

        return redirect()
            ->route('superadmin.firms.show', $firm)
            ->with('success', 'Firm created. Add an admin separately from Admins.');
    }

    public function show(Firm $firm): View
    {
        $admins = User::query()
            ->withoutGlobalScopes()
            ->where('firm_id', $firm->id)
            ->with('roles')
            ->orderBy('name')
            ->get();

        return view('superadmin.firms.show', [
            'firm' => $firm,
            'admins' => $admins,
        ]);
    }

    public function update(Request $request, Firm $firm): RedirectResponse
    {
        $data = $request->validate([
            'firm_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        $this->platform->updateFirm($firm, $data);

        return redirect()
            ->route('superadmin.firms.show', $firm)
            ->with('success', 'Firm updated.');
    }

    public function updateStatus(Request $request, Firm $firm): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,suspended'],
        ]);

        $this->platform->setFirmStatus($firm, $data['status']);

        return back()->with('success', 'Firm status updated to '.$data['status'].'.');
    }
}
