<?php

namespace App\Domain\Services;

use App\Domain\Audit\AuditLogService;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\DB;

class ServiceCatalogService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function createCategory(array $data): ServiceCategory
    {
        $category = ServiceCategory::query()->create($data);
        $this->audit->log('service_category.created', $category, null, $category->toArray());

        return $category;
    }

    public function createService(array $data): Service
    {
        $service = Service::query()->create($data);
        $this->audit->log('service.created', $service, null, $service->toArray());

        return $service;
    }

    public function updateService(Service $service, array $data): Service
    {
        $old = $service->toArray();
        $service->fill($data)->save();
        $this->audit->log('service.updated', $service, $old, $service->toArray());

        return $service->fresh();
    }

    public function syncLawyers(Service $service, array $lawyerIds): void
    {
        $service->lawyers()->sync($lawyerIds);
    }
}
