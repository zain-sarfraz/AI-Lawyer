<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Lawyers\LawyerService;
use App\Http\Controllers\Controller;
use App\Models\Lawyer;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LawyerController extends Controller implements HasMiddleware
{
    public function __construct(private readonly LawyerService $lawyers) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:lawyers.manage'),
        ];
    }

    public function index(): View
    {
        $this->authorize('viewAny', Lawyer::class);

        return view('admin.lawyers.index', [
            'lawyers' => Lawyer::query()->with('user', 'services')->paginate(25),
            'formLawyer' => new Lawyer(['status' => 'active']),
            'formServices' => Service::query()->orderBy('name')->get(),
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

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'experience' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'string'],
            'service_ids' => ['array'],
            'service_ids.*' => ['exists:services,id'],
        ]);

        $this->lawyers->create(
            [
                'title' => $data['title'] ?? null,
                'phone' => $data['phone'] ?? null,
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
            $data['service_ids'] ?? []
        );

        return redirect()->route('lawyers.index')->with('success', 'Lawyer created.');
    }

    public function show(Lawyer $lawyer): RedirectResponse
    {
        $this->authorize('view', $lawyer);

        return redirect()->route('lawyers.index', ['details' => $lawyer->id]);
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

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($lawyer->user_id)],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'experience' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'string'],
            'service_ids' => ['array'],
            'service_ids.*' => ['exists:services,id'],
        ]);

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
            'phone' => $data['phone'] ?? null,
            'experience' => $data['experience'] ?? null,
            'bio' => $data['bio'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'status' => $data['status'],
        ], $data['service_ids'] ?? []);

        return redirect()->route('lawyers.index')->with('success', 'Lawyer updated.');
    }
}
