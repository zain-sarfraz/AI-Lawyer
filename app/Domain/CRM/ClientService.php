<?php

namespace App\Domain\CRM;

use App\Domain\Audit\AuditLogService;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ClientService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        // Keep Clients in sync with callers who arrived via phone/AI booking.
        $this->syncCallCustomers();

        $query = Client::query()
            ->with([
                'services',
                'appointments' => fn ($q) => $q->with(['service', 'lawyer.user'])->latest('id')->limit(10),
            ])
            ->withCount('appointments')
            // Prefer call / AI customers in the clients list.
            ->where(function ($q) {
                $q->whereHas('appointments', function ($a) {
                    $a->where('source', 'phone_ai')->orWhereNotNull('call_id');
                })
                    ->orWhereHas('leads', fn ($l) => $l->where('source', 'phone_ai'))
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('calls')
                            ->whereColumn('calls.firm_id', 'clients.firm_id')
                            ->where(function ($c) {
                                $c->whereColumn('calls.client_id', 'clients.id')
                                    ->orWhereColumn('calls.caller_phone', 'clients.phone');
                            });
                    });
            })
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

    /**
     * Create or update a client profile for a caller (phone AI / staff booking).
     *
     * @param  array{name?: ?string, phone?: ?string, email?: ?string, notes?: ?string, service_id?: int|null}  $data
     */
    public function upsertFromCaller(array $data): ?Client
    {
        $phoneRaw = isset($data['phone']) ? trim((string) $data['phone']) : '';
        $phone = preg_replace('/\D+/', '', $phoneRaw) ?: $phoneRaw;
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        if ($phone === '' && $name === '') {
            return null;
        }

        $client = $phone !== '' ? $this->findByPhone($phone) : null;

        $serviceIds = array_values(array_filter([
            isset($data['service_id']) ? (int) $data['service_id'] : null,
        ]));

        if ($client) {
            return $this->update($client, array_filter([
                'name' => $name !== '' ? $name : $client->name,
                'phone' => $phone !== '' ? $phone : $client->phone,
                'email' => $data['email'] ?? $client->email,
                'notes' => $data['notes'] ?? $client->notes,
                'status' => 'active',
            ], fn ($v) => $v !== null), $serviceIds !== [] ? $serviceIds : null);
        }

        return $this->create([
            'name' => $name !== '' ? $name : ($phone !== '' ? $phone : 'Caller'),
            'phone' => $phone !== '' ? $phone : null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'active',
        ], $serviceIds);
    }

    /**
     * Upsert client rows for recent call / AI-booked leads so they appear under Clients.
     */
    public function syncCallCustomers(int $limit = 100): void
    {
        $leads = Lead::query()
            ->where('source', 'phone_ai')
            ->where(function ($q) {
                $q->whereNotNull('phone')->orWhereNotNull('name');
            })
            ->latest('id')
            ->limit($limit)
            ->get(['name', 'phone', 'email', 'notes', 'service_id']);

        foreach ($leads as $lead) {
            $this->upsertFromCaller([
                'name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'notes' => $lead->notes,
                'service_id' => $lead->service_id,
            ]);
        }

        $calls = Call::query()
            ->whereNotNull('caller_phone')
            ->where('caller_phone', '!=', '')
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'caller_phone', 'lead_id', 'client_id']);

        foreach ($calls as $call) {
            $lead = $call->lead_id ? Lead::query()->find($call->lead_id) : null;
            $client = $this->upsertFromCaller([
                'name' => $lead?->name,
                'phone' => $call->caller_phone ?: $lead?->phone,
                'email' => $lead?->email,
                'notes' => $lead?->notes,
                'service_id' => $lead?->service_id,
            ]);

            if ($client && ! $call->client_id) {
                Call::query()->whereKey($call->id)->update(['client_id' => $client->id]);
            }
        }

        // Link appointments that have a lead/call but missing client_id.
        Appointment::query()
            ->whereNull('client_id')
            ->where(function ($q) {
                $q->where('source', 'phone_ai')->orWhereNotNull('call_id')->orWhereNotNull('lead_id');
            })
            ->with('lead')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->each(function (Appointment $appointment): void {
                $client = $this->upsertFromCaller([
                    'name' => $appointment->lead?->name,
                    'phone' => $appointment->lead?->phone,
                    'email' => $appointment->lead?->email,
                    'notes' => $appointment->lead?->notes,
                    'service_id' => $appointment->service_id,
                ]);
                if ($client) {
                    $appointment->update(['client_id' => $client->id]);
                }
            });
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

        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if ($digits === '') {
            return Client::query()->where('phone', $phone)->latest()->first();
        }

        $tail = strlen($digits) >= 9 ? substr($digits, -9) : $digits;

        return Client::query()
            ->where(function ($q) use ($phone, $digits, $tail) {
                $q->where('phone', $phone)
                    ->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone,''),' ',''),'-',''),'+',''),'(','') LIKE ?",
                        ['%'.$digits.'%']
                    )
                    ->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone,''),' ',''),'-',''),'+',''),'(','') LIKE ?",
                        ['%'.$tail.'%']
                    );
            })
            ->latest()
            ->first();
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
