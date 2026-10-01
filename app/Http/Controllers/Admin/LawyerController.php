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
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Lawyer::class);

        return view('admin.lawyers.form', [
            'lawyer' => new Lawyer(['status' => 'active']),
            'services' => Service::query()->active()->orderBy('name')->get(),
            'selectedServices' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Lawyer::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
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

    public function edit(Lawyer $lawyer): View
    {
        $this->authorize('update', $lawyer);

        return view('admin.lawyers.form', [
            'lawyer' => $lawyer->load('user'),
            'services' => Service::query()->active()->orderBy('name')->get(),
            'selectedServices' => $lawyer->services()->pluck('services.id')->all(),
        ]);
    }

    public function update(Request $request, Lawyer $lawyer): RedirectResponse
    {
        $this->authorize('update', $lawyer);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'string'],
            'service_ids' => ['array'],
            'service_ids.*' => ['exists:services,id'],
        ]);
        $this->lawyers->update($lawyer, [
            'title' => $data['title'] ?? null,
            'bio' => $data['bio'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'status' => $data['status'],
        ], $data['service_ids'] ?? []);

        return redirect()->route('lawyers.index')->with('success', 'Lawyer updated.');
    }
}
