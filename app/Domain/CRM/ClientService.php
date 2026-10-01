<?php

namespace App\Domain\CRM;

use App\Domain\Audit\AuditLogService;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ClientService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Client::query()->latest();
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

    public function create(array $data): Client
    {
        $client = Client::query()->create($data);
        $this->audit->log('client.created', $client, null, $client->toArray());

        return $client;
    }

    public function update(Client $client, array $data): Client
    {
        $old = $client->toArray();
        $client->fill($data)->save();
        $this->audit->log('client.updated', $client, $old, $client->toArray());

        return $client->fresh();
    }

    public function convertFromLead(Lead $lead, array $overrides = []): Client
    {
        return DB::transaction(function () use ($lead, $overrides) {
            $client = $this->create([
                'name' => $overrides['name'] ?? $lead->name ?? 'Client',
                'phone' => $overrides['phone'] ?? $lead->phone,
                'email' => $overrides['email'] ?? $lead->email,
                'notes' => $overrides['notes'] ?? $lead->notes,
                'status' => 'active',
            ]);
            $client->leads()->syncWithoutDetaching([$lead->id]);
            $lead->update(['status' => 'converted']);

            return $client;
        });
    }

    public function findByPhone(?string $phone): ?Client
    {
        if (! $phone) {
            return null;
        }

        return Client::query()->where('phone', $phone)->latest()->first();
    }
}
