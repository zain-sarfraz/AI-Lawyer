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

    public function index(Request $request): View
    {
        $query = Firm::query()->withCount('users');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('firm_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('status', 'active');
            } elseif ($status === 'inactive' || $status === 'suspended') {
                $query->where('status', '!=', 'active');
            }
        }

        $firms = $query->orderBy('id', 'asc')->paginate(10)->withQueryString();

        $totalFirms = Firm::query()->count();
        $activeFirms = Firm::query()->where('status', 'active')->count();
        $inactiveFirms = Firm::query()->where('status', '!=', 'active')->count();

        $recentFirms = Firm::query()->latest('id')->limit(5)->get();

        return view('superadmin.firms.index', compact(
            'firms',
            'totalFirms',
            'activeFirms',
            'inactiveFirms',
            'recentFirms'
        ));
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
            ->with('success', 'Firm created. Add lawyers from the Lawyers page.');
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

    public function destroy(Firm $firm): RedirectResponse
    {
        $name = $firm->firm_name;
        $this->platform->deleteFirm($firm);

        return redirect()
            ->route('superadmin.firms.index')
            ->with('success', $name.' deleted.');
    }
}
