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
        if ($contextId === null) {
            throw new \RuntimeException('No firm context is set. Firm workspace routes require a firm member.');
        }

        $firm = FirmSetting::query()->find($contextId);
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
