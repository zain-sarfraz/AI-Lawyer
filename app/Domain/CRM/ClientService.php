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
        $query = Client::query()
            ->with([
                'services',
                'appointments' => fn ($q) => $q->with(['service', 'lawyer.user'])->latest('id')->limit(10),
            ])
            ->withCount('appointments')
            ->latest();
        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('client_number', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function create(array $data, array $serviceIds = []): Client
    {
        return DB::transaction(function () use ($data, $serviceIds) {
            $client = Client::query()->create($data);
            if ($serviceIds !== []) {
                $client->services()->sync($serviceIds);
            }
            $this->audit->log('client.created', $client, null, $client->toArray());

            return $client->load('services');
        });
    }

    public function update(Client $client, array $data, ?array $serviceIds = null): Client
    {
        return DB::transaction(function () use ($client, $data, $serviceIds) {
            $old = $client->toArray();
            $client->fill($data)->save();
            if (is_array($serviceIds)) {
                $client->services()->sync($serviceIds);
            }
            $this->audit->log('client.updated', $client, $old, $client->fresh()->toArray());

            return $client->fresh()->load('services');
        });
    }

    public function convertFromLead(Lead $lead, array $overrides = []): Client
    {
        return DB::transaction(function () use ($lead, $overrides) {
            $serviceIds = array_values(array_filter([
                $overrides['service_id'] ?? $lead->service_id,
            ]));

            $client = $this->create([
                'name' => $overrides['name'] ?? $lead->name ?? 'Client',
                'phone' => $overrides['phone'] ?? $lead->phone,
                'email' => $overrides['email'] ?? $lead->email,
                'notes' => $overrides['notes'] ?? $lead->notes,
                'status' => 'active',
            ], $serviceIds);
            $client->leads()->syncWithoutDetaching([$lead->id]);
            $lead->update(['status' => 'converted']);

            return $client;
        });
    }

    public function findByPhone(mixed $phone): ?Client
    {
        if (is_array($phone)) {
            $phone = (string) ($phone['value'] ?? $phone['number'] ?? $phone['phone'] ?? reset($phone) ?? '');
        }

        $phone = is_string($phone) ? trim($phone) : '';
        if ($phone === '') {
            return null;
        }

        return Client::query()->where('phone', $phone)->latest()->first();
    }

    public function updateStatus(Client $client, string $status): Client
    {
        $old = $client->toArray();
        $client->update(['status' => $status]);
        $this->audit->log('client.status', $client, $old, $client->fresh()->toArray());

        return $client->fresh()->load('services');
    }

    public function delete(Client $client): string
    {
        return DB::transaction(function () use ($client) {
            $old = $client->toArray();

            if ($client->appointments()->exists()) {
                $client->update(['status' => 'inactive']);
                $this->audit->log('client.deactivated', $client, $old, $client->fresh()->toArray());

                return 'Client has appointments, so the profile was set to inactive instead of deleted.';
            }

            $client->services()->detach();
            $client->leads()->detach();
            $client->delete();
            $this->audit->log('client.deleted', null, $old, null);

            return 'Client deleted.';
        });
    }
}
