<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Services\ServiceCatalogService;
use App\Http\Controllers\Controller;
use App\Models\Lawyer;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceCatalogService $catalog) {}

    public function index(): View
    {
        return view('admin.services.index', [
            'services' => Service::query()->with('category')->orderBy('name')->paginate(25),
            'categories' => ServiceCategory::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.services.form', [
            'service' => new Service(['booking_enabled' => true, 'ai_enabled' => true, 'status' => 'active', 'currency' => 'USD', 'duration_minutes' => 30]),
            'categories' => ServiceCategory::query()->orderBy('name')->get(),
            'lawyers' => Lawyer::query()->active()->with('user')->get(),
            'selectedLawyers' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $service = $this->catalog->createService($data);
        $this->catalog->syncLawyers($service, $request->input('lawyer_ids', []));

        return redirect()->route('services.index')->with('success', 'Service created.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', [
            'service' => $service,
            'categories' => ServiceCategory::query()->orderBy('name')->get(),
            'lawyers' => Lawyer::query()->active()->with('user')->get(),
            'selectedLawyers' => $service->lawyers()->pluck('lawyers.id')->all(),
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->catalog->updateService($service, $this->validated($request));
        $this->catalog->syncLawyers($service, $request->input('lawyer_ids', []));

        return redirect()->route('services.index')->with('success', 'Service updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'exists:service_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:8'],
            'duration_minutes' => ['required', 'integer', 'min:5'],
            'buffer_minutes' => ['nullable', 'integer', 'min:0'],
            'booking_enabled' => ['sometimes', 'boolean'],
            'ai_enabled' => ['sometimes', 'boolean'],
            'status' => ['required', 'string'],
        ]) + [
            'booking_enabled' => $request->boolean('booking_enabled'),
            'ai_enabled' => $request->boolean('ai_enabled'),
        ];
    }
}
