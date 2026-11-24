<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Billing\PlanCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(PlanCatalog $plans): array
    {
        $planIds = $plans->ids();
        $selectedPlan = (string) $this->input('plan');
        $isFirmPlan = false;

        if ($plans->exists($selectedPlan)) {
            $summary = $plans->get($selectedPlan);
            $isFirmPlan = ($summary['account_type'] ?? '') === 'firm';
        }

        return [
            'plan' => ['required', 'string', Rule::in($planIds)],
            'interval' => ['required', 'string', Rule::in(['monthly', 'yearly'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'firm_name' => [$isFirmPlan ? 'required' : 'nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'accept_terms' => ['accepted'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'firm_name.required' => 'Firm name is required for law firm accounts.',
            'accept_terms.accepted' => 'You must agree to the Terms of Service and Privacy Policy.',
        ];
    }
}
