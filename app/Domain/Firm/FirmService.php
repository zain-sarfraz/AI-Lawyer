<?php

namespace App\Domain\Firm;

use App\Domain\Audit\AuditLogService;
use App\Models\Firm;
use App\Models\FirmSetting;
use App\Models\Lawyer;
use Illuminate\Support\Facades\DB;

class FirmService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function current(): FirmSetting
    {
        $contextId = app(FirmContext::class)->id();
        $user = auth()->user();

        if ($contextId === null) {
            if ($user && ($user->isIndividual() || $user->role === 'individual_lawyer')) {
                $firm = FirmSetting::withoutGlobalScopes()
                    ->where(function ($q) use ($user) {
                        $q->where('owner_id', $user->id)
                          ->orWhere('id', $user->firm_id);
                    })
                    ->first();

                if (! $firm) {
                    $practiceName = $user->name ? "{$user->name}'s Practice" : 'Individual Legal Practice';
                    $firm = FirmSetting::withoutGlobalScopes()->create([
                        'name' => $practiceName,
                        'firm_name' => $practiceName,
                        'legal_name' => $user->name,
                        'owner_id' => $user->id,
                        'account_type' => 'individual',
                        'plan' => 'starter',
                        'status' => 'active',
                        'subscription_status' => 'active',
                        'max_lawyers' => 1,
                        'timezone' => Firm::DEFAULT_TIMEZONE,
                        'currency' => 'USD',
                    ]);
                }

                if ($user->firm_id !== $firm->id) {
                    $user->firm_id = $firm->id;
                    $user->saveQuietly();
                }

                Lawyer::withoutGlobalScopes()
                    ->where('user_id', $user->id)
                    ->whereNull('firm_id')
                    ->update(['firm_id' => $firm->id]);

                return $firm;
            }

            throw new \RuntimeException('No firm context is set. Firm workspace routes require a firm member.');
        }

        $firm = FirmSetting::query()->find($contextId) ?: FirmSetting::withoutGlobalScopes()->find($contextId);
        if (! $firm instanceof FirmSetting) {
            throw new \RuntimeException('Firm workspace not found for the current context.');
        }

        return $firm;
    }

    public function update(array $data): FirmSetting
    {
        return DB::transaction(function () use ($data) {
            $firm = $this->current();
            $old = $firm->only(array_keys($data));
            $firm->fill($data);
            $firm->save();
            $this->audit->log('firm.settings.updated', $firm, $old, $firm->only(array_keys($data)));

            return $firm->fresh();
        });
    }
}
