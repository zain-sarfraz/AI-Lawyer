<?php

namespace App\Domain\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public function log(
        string $action,
        ?Model $entity = null,
        ?array $old = null,
        ?array $new = null,
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity?->getKey(),
            'old_values_json' => $old,
            'new_values_json' => $new,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }
}
