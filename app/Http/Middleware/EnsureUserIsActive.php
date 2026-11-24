<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Superadmin has unrestricted platform access
            if ($user->isSuperAdmin()) {
                return $next($request);
            }

            // Pending payment: redirect to complete payment
            if ($user->status === UserStatus::PendingPayment) {
                $plan = $user->pending_plan ?: ($user->firm?->plan ?: 'starter');
                $interval = $user->pending_interval ?: ($user->firm?->billing_interval ?: 'monthly');

                return redirect()->route('checkout.show', [
                    'plan' => $plan,
                    'interval' => $interval,
                    'resume' => 1,
                    'email' => $user->email,
                ])->with('error', 'Please complete payment to activate your account.');
            }

            // Cancelled: block access and redirect to resubscribe
            if ($user->status === UserStatus::Cancelled) {
                $plan = $user->firm?->plan ?: 'starter';

                return redirect()->route('checkout.show', ['plan' => $plan])
                    ->with('error', 'Your subscription has been cancelled. Please resubscribe to regain access.');
            }

            // Past due: share a warning banner for the view, but do not lock out immediately
            if ($user->status === UserStatus::PastDue) {
                view()->share('pastDueWarning', 'Your payment is past due. Please update your billing details to maintain full access.');
            }

            // Inactive or suspended
            if (! $user->isActive() && $user->status !== UserStatus::PastDue) {
                auth()->logout();

                return redirect()->route('login')->withErrors(['email' => 'Your account is inactive.']);
            }

            // Firm checks
            if ($user->firm_id) {
                $firmStatus = optional($user->firm)->status ?? 'active';

                if ($firmStatus === 'pending') {
                    return redirect()->route('checkout.show', [
                        'plan' => $user->firm->plan ?: 'growth',
                        'interval' => $user->firm->billing_interval ?: 'monthly',
                        'resume' => 1,
                    ])->with('error', 'Your firm workspace is pending payment activation.');
                }

                if ($firmStatus === 'suspended') {
                    auth()->logout();

                    return redirect()->route('login')->withErrors(['email' => 'This firm workspace is suspended.']);
                }
            }
        }

        return $next($request);
    }
}
