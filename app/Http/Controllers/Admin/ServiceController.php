<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Services\ServiceCatalogService;
use App\Http\Controllers\Controller;
use App\Models\Lawyer;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ServiceController extends Controller implements HasMiddleware
{
    public function __construct(private readonly ServiceCatalogService $catalog) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:services.manage'),
        ];
    }

    public function index(): View
    {
        $this->authorize('viewAny', Service::class);

        return view('admin.services.index', [
            'services' => Service::query()->with('category')->orderBy('name')->paginate(25),
            'categories' => ServiceCategory::query()->orderBy('sort_order')->get(),
            'formService' => new Service(['booking_enabled' => true, 'ai_enabled' => true, 'status' => 'active', 'currency' => 'USD', 'duration_minutes' => 30]),
            'formCategories' => ServiceCategory::query()->orderBy('name')->get(),
            'formLawyers' => Lawyer::query()->active()->with('user')->get(),
        ]);
    }

    public function create(): RedirectResponse
    {
        $this->authorize('create', Service::class);

        return redirect()->route('services.index', ['open' => 'create-service']);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Service::class);

        $this->catalog->createService($this->validated($request, [
            'booking_enabled' => true,
            'ai_enabled' => true,
        ]));

        return redirect()->route('services.index')->with('success', 'Service created.');
    }

    public function edit(Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        return redirect()->route('services.index', ['open' => 'edit-service-'.$service->id]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $this->catalog->updateService($service, $this->validated($request, [
            'booking_enabled' => (bool) $service->booking_enabled,
            'ai_enabled' => (bool) $service->ai_enabled,
        ]));

        return redirect()->route('services.index')->with('success', 'Service updated.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);

        $message = $this->catalog->delete($service);

        return redirect()->route('services.index')->with('success', $message);
    }

    private function validated(Request $request, array $defaults = []): array
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:service_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:8'],
            'duration_minutes' => ['required', 'integer', 'min:5'],
            'buffer_minutes' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string'],
        ]);

        return $data + [
            'booking_enabled' => $defaults['booking_enabled'] ?? true,
            'ai_enabled' => $defaults['ai_enabled'] ?? true,
        ];
    }
}
