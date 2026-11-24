<?php

declare(strict_types=1);

namespace App\Domain\Billing;

use App\Domain\Firm\FirmContext;
use App\Domain\Lawyers\LawyerService;
use App\Domain\Platform\PlatformFirmService;
use App\Models\Firm;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionOnboardingService
{
    public function __construct(
        private readonly PlanCatalog $plans,
        private readonly PlatformFirmService $firms,
        private readonly LawyerService $lawyers,
        private readonly FirmContext $firmContext,
    ) {}

    /**
     * Create firm + first lawyer after a successful Stripe (or dummy) payment.
     *
     * @param  array{
     *   plan: string,
     *   interval: string,
     *   name: string,
     *   email: string,
     *   password: string,
     *   firm_name?: ?string,
     *   phone?: ?string
     * }  $account
     * @param  array{id: string, customer_id: string, subscription_id: string}  $payment
     */
    public function provision(array $account, array $payment): User
    {
        $summary = $this->plans->checkoutSummary($account['plan'], $account['interval']);
        $email = strtolower(trim($account['email']));

        // Same email may already exist in another firm; that is allowed.
        // Platform Superadmin emails stay reserved.
        if (User::emailTakenInFirm($email, null)) {
            throw ValidationException::withMessages([
                'email' => 'This email is reserved for a platform account.',
            ]);
        }

        $firmName = trim((string) ($account['firm_name'] ?? ''));
        if ($firmName === '') {
            $firmName = $summary['account_type'] === 'individual'
                ? $account['name']."'s Practice"
                : $account['name'].' Law Firm';
        }

        return DB::transaction(function () use ($account, $payment, $summary, $email, $firmName) {
            $firm = $this->firms->createFirm([
                'firm_name' => $firmName,
                'email' => $email,
                'phone' => $account['phone'] ?? null,
                'timezone' => Firm::DEFAULT_TIMEZONE,
                'currency' => 'USD',
            ]);

            $firm->forceFill([
                'account_type' => $summary['account_type'],
                'plan' => $summary['id'],
                'billing_interval' => $summary['interval'],
                'subscription_status' => 'active',
                'max_lawyers' => $summary['max_lawyers'],
                'stripe_customer_id' => $payment['customer_id'],
                'stripe_subscription_id' => $payment['subscription_id'],
                'stripe_payment_intent_id' => $payment['id'],
                'subscribed_at' => now(),
            ])->save();

            return $this->firmContext->run((int) $firm->id, function () use ($account, $email, $summary) {
                $lawyer = $this->lawyers->create(
                    [
                        'title' => $summary['account_type'] === 'firm' ? 'Firm Admin' : 'Lawyer',
                        'phone' => $account['phone'] ?? null,
                        'status' => 'active',
                        'specialties' => [],
                    ],
                    [
                        'name' => $account['name'],
                        'email' => $email,
                        'password' => $account['password'],
                    ],
                );

                $user = $lawyer->user;
                if ($summary['account_type'] === 'firm') {
                    $user->syncRoles(['FirmAdmin']);
                } else {
                    $user->syncRoles(['Lawyer']);
                }

                return $user->fresh(['roles', 'lawyer', 'firm']);
            });
        });
    }
}
