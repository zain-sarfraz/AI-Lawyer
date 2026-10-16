<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Users\FirmUserService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class UserController extends Controller implements HasMiddleware
{
    public function __construct(private readonly FirmUserService $users) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:users.manage'),
        ];
    }

    public function index(): View
    {
        $users = User::query()
            ->with(['roles', 'lawyer'])
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Lawyer', 'Staff']))
            ->latest('id')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:Lawyer,Staff'],
            'phone' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $this->users->create($data);

        return redirect()
            ->route('users.index')
            ->with('success', $user->name.' added. They can sign in and see their own appointments and records.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $this->users->setStatus($user, $data['status']);

        return back()->with('success', 'User status updated.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:Lawyer,Staff'],
            'status' => ['nullable', 'in:active,inactive,suspended'],
            'phone' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $this->users->update($user, $data);

        return redirect()
            ->route('users.index')
            ->with('success', $user->name.' updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $name = $user->name;
        $this->users->delete($user);

        return redirect()
            ->route('users.index')
            ->with('success', $name.' was deleted.');
    }
}
