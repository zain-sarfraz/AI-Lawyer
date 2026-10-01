<?php

namespace App\Domain\Firm;

use App\Domain\Audit\AuditLogService;
use App\Models\FirmSetting;
use Illuminate\Support\Facades\DB;

class FirmService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function current(): FirmSetting
    {
        return FirmSetting::query()->firstOr(function () {
            return FirmSetting::query()->create([
                'firm_name' => 'Law Firm',
                'timezone' => config('app.timezone', 'UTC'),
                'currency' => 'USD',
                'receptionist_name' => 'Virtual Receptionist',
            ]);
        });
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
