<?php

namespace App\Domain\CRM;

use App\Domain\Audit\AuditLogService;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LeadService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Lead::query()->with(['service', 'lawyer.user'])->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): Lead
    {
        $lead = Lead::query()->create([
            ...$data,
            'status' => $data['status'] ?? LeadStatus::New->value,
            'last_contact_at' => $data['last_contact_at'] ?? now(),
        ]);
        $this->audit->log('lead.created', $lead, null, $lead->toArray());

        return $lead;
    }

    public function update(Lead $lead, array $data): Lead
    {
        $old = $lead->toArray();
        $lead->fill($data);
        $lead->save();
        $this->audit->log('lead.updated', $lead, $old, $lead->toArray());

        return $lead->fresh();
    }

    public function findByPhone(mixed $phone): ?Lead
    {
        if (is_array($phone)) {
            $phone = (string) ($phone['value'] ?? $phone['number'] ?? $phone['phone'] ?? reset($phone) ?? '');
        }

        $phone = is_string($phone) ? trim($phone) : '';
        if ($phone === '') {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $phone);

        return Lead::query()
            ->where('phone', $phone)
            ->orWhere('phone', 'like', '%'.$normalized)
            ->latest()
            ->first();
    }

    public function createOrUpdateFromCaller(array $data): Lead
    {
        if (isset($data['phone']) && is_array($data['phone'])) {
            $data['phone'] = (string) ($data['phone']['value'] ?? $data['phone']['number'] ?? $data['phone']['phone'] ?? reset($data['phone']) ?? '');
        }
        if (isset($data['name']) && is_array($data['name'])) {
            $data['name'] = (string) ($data['name']['value'] ?? reset($data['name']) ?? '');
        }
        if (isset($data['email']) && is_array($data['email'])) {
            $data['email'] = (string) ($data['email']['value'] ?? reset($data['email']) ?? '');
        }

        return DB::transaction(function () use ($data) {
            $existing = $this->findByPhone($data['phone'] ?? null);
            if ($existing) {
                return $this->update($existing, array_filter([
                    'name' => $data['name'] ?? $existing->name,
                    'email' => $data['email'] ?? $existing->email,
                    'service_id' => $data['service_id'] ?? $existing->service_id,
                    'lawyer_id' => $data['lawyer_id'] ?? $existing->lawyer_id,
                    'notes' => $data['notes'] ?? $existing->notes,
                    'source' => $data['source'] ?? $existing->source,
                    'last_contact_at' => now(),
                ], fn ($v) => $v !== null));
            }

            return $this->create([
                ...$data,
                'source' => $data['source'] ?? 'phone_ai',
                'last_contact_at' => now(),
            ]);
        });
    }
}
