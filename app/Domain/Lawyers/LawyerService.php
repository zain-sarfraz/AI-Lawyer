<?php

namespace App\Domain\Lawyers;

use App\Domain\Audit\AuditLogService;
use App\Domain\Firm\FirmContext;
use App\Models\Firm;
use App\Models\Lawyer;
use App\Models\User;
use App\Models\WorkingHour;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LawyerService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function create(array $data, ?array $userData = null, array $serviceIds = [], array $jurisdictionIds = []): Lawyer
    {
        return DB::transaction(function () use ($data, $userData, $serviceIds, $jurisdictionIds) {
            $this->assertWithinPlanLawyerLimit(isset($data['firm_id']) ? (int) $data['firm_id'] : null);

            if ($userData) {
                $user = User::query()->create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => !empty($userData['password']) ? $userData['password'] : '11223344',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);
                $user->assignRole('Lawyer');
                $data['user_id'] = $user->id;
            }

            $lawyer = Lawyer::query()->create($data);
            if ($serviceIds) {
                $lawyer->services()->sync($serviceIds);
            }
            $lawyer->jurisdictions()->sync($jurisdictionIds);

            // Default weekly windows (Mon–Fri 09:00–17:00). Lawyers can edit under Availability.
            foreach ([1, 2, 3, 4, 5] as $dayOfWeek) {
                WorkingHour::query()->create([
                    'lawyer_id' => $lawyer->id,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => '09:00:00',
                    'end_time' => '17:00:00',
                    'status' => 'active',
                ]);
            }

            $this->audit->log('lawyer.created', $lawyer, null, $lawyer->toArray());

            return $lawyer->load('user', 'services', 'jurisdictions');
        });
    }

    public function update(Lawyer $lawyer, array $data, ?array $serviceIds = null, ?array $jurisdictionIds = null): Lawyer
    {
        $old = $lawyer->toArray();
        $lawyer->fill($data)->save();
        if (is_array($serviceIds)) {
            $lawyer->services()->sync($serviceIds);
        }
        if (is_array($jurisdictionIds)) {
            $lawyer->jurisdictions()->sync($jurisdictionIds);
        }
        $this->audit->log('lawyer.updated', $lawyer, $old, $lawyer->fresh()->toArray());

        return $lawyer->fresh()->load('user', 'services', 'jurisdictions');
    }

    public function delete(Lawyer $lawyer): string
    {
        return DB::transaction(function () use ($lawyer) {
            $old = $lawyer->toArray();

            $lawyer->services()->detach();
            $lawyer->jurisdictions()->detach();
            $lawyer->appointments()->delete();
            $user = $lawyer->user;
            $lawyer->delete();
            $user?->delete();
            $this->audit->log('lawyer.deleted', null, $old, null);

            return 'Lawyer deleted.';
        });
    }

    private function assertWithinPlanLawyerLimit(?int $firmId = null): void
    {
        $firmId ??= app(FirmContext::class)->id();
        if ($firmId === null) {
            return;
        }

        $firm = Firm::query()->find($firmId);
        if (! $firm || $firm->canAddLawyer()) {
            return;
        }

        $max = $firm->resolvedMaxLawyers() ?? 1;

        throw ValidationException::withMessages([
            'plan' => "Your {$firm->plan} (individual) plan allows only {$max} lawyer. Upgrade to Growth for a multi-lawyer firm workspace.",
        ]);
    }
}
