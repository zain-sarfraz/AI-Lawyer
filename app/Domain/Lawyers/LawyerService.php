<?php

namespace App\Domain\Lawyers;

use App\Domain\Audit\AuditLogService;
use App\Domain\Firm\FirmContext;
use App\Models\Firm;
use App\Models\Jurisdiction;
use App\Models\Lawyer;
use App\Models\User;
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
            $lawyer->appointments()->withoutGlobalScopes()->delete();
            $lawyer->workingHours()->withoutGlobalScopes()->delete();
            $lawyer->scheduleBreaks()->withoutGlobalScopes()->delete();
            $lawyer->availabilityExceptions()->withoutGlobalScopes()->delete();
            $user = $lawyer->user_id ? User::query()->withoutGlobalScopes()->find($lawyer->user_id) : null;
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

    /**
     * Provision or sync a Lawyer profile when a checkout user activates, preserving
     * their chosen country code and case-law jurisdictions (defaulting by country).
     */
    public function provisionForActivatedUser(User $user, ?int $firmId = null, string $title = 'Individual Lawyer'): Lawyer
    {
        $onboarding = is_array($user->onboarding) ? $user->onboarding : [];
        $country = strtoupper(trim((string) ($onboarding['preferred_country'] ?? '')));
        if (! in_array($country, ['PK', 'GB', 'US', 'CA', 'AU'], true)) {
            $country = null;
        }
        $phone = ! empty($onboarding['preferred_phone']) ? (string) $onboarding['preferred_phone'] : null;

        $lawyer = Lawyer::withoutGlobalScopes()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'firm_id' => $firmId,
                'title' => $title,
                'status' => 'active',
                'country' => $country,
                'phone' => $phone,
                'specialties' => [],
            ]
        );

        if ($lawyer->jurisdictions()->count() === 0) {
            $preferredIds = array_values(array_filter(array_map('intval', (array) ($onboarding['preferred_jurisdiction_ids'] ?? []))));
            $syncIds = [];
            if ($preferredIds !== []) {
                $syncIds = Jurisdiction::query()->active()->whereIn('id', $preferredIds)->pluck('id')->all();
            } elseif ($country !== null) {
                $syncIds = Jurisdiction::query()->active()->where('country_code', $country)->pluck('id')->all();
            }

            if ($syncIds !== []) {
                $lawyer->jurisdictions()->sync($syncIds);
            }
        }

        return $lawyer;
    }
}
