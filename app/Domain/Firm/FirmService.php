<?php

namespace App\Domain\Firm;

use App\Domain\Audit\AuditLogService;
use App\Models\Firm;
use App\Models\FirmSetting;
use Illuminate\Support\Facades\DB;

class FirmService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function current(): FirmSetting
    {
        $contextId = app(FirmContext::class)->id();
        if ($contextId !== null) {
            $firm = FirmSetting::query()->find($contextId);
            if ($firm instanceof FirmSetting) {
                return $firm;
            }
        }

        $existing = FirmSetting::query()->first();
        if ($existing instanceof FirmSetting) {
            return $existing;
        }

        $created = FirmSetting::query()->create([
            'firm_name' => 'Law Firm',
            'timezone' => Firm::DEFAULT_TIMEZONE,
            'currency' => 'USD',
            'receptionist_name' => 'Virtual Receptionist',
        ]);
        app(FirmContext::class)->set($created->id);

        return $created;
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
