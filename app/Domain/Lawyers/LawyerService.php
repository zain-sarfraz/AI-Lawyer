<?php

namespace App\Domain\Lawyers;

use App\Domain\Audit\AuditLogService;
use App\Models\Lawyer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LawyerService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function create(array $data, ?array $userData = null, array $serviceIds = []): Lawyer
    {
        return DB::transaction(function () use ($data, $userData, $serviceIds) {
            if ($userData) {
                $user = User::query()->create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password'] ?? 'password'),
                    'role_id' => $userData['role_id'] ?? null,
                    'status' => 'active',
                ]);
                $data['user_id'] = $user->id;
            }

            $lawyer = Lawyer::query()->create($data);
            if ($serviceIds) {
                $lawyer->services()->sync($serviceIds);
            }
            $this->audit->log('lawyer.created', $lawyer, null, $lawyer->toArray());

            return $lawyer->load('user', 'services');
        });
    }

    public function update(Lawyer $lawyer, array $data, array $serviceIds = null): Lawyer
    {
        $old = $lawyer->toArray();
        $lawyer->fill($data)->save();
        if (is_array($serviceIds)) {
            $lawyer->services()->sync($serviceIds);
        }
        $this->audit->log('lawyer.updated', $lawyer, $old, $lawyer->fresh()->toArray());

        return $lawyer->fresh()->load('user', 'services');
    }
}
