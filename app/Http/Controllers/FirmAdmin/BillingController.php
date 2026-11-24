<?php

declare(strict_types=1);

namespace App\Http\Controllers\FirmAdmin;

use App\Domain\Billing\PlanCatalog;
use App\Http\Controllers\Controller;
use App\Models\Firm;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(private readonly PlanCatalog $plans) {}

    public function __invoke(): View
    {
        $firm = Firm::current();
        $summary = null;

        if ($firm->plan && $this->plans->exists((string) $firm->plan)) {
            $summary = $this->plans->checkoutSummary(
                (string) $firm->plan,
                (string) ($firm->billing_interval ?: 'monthly'),
            );
        }

        return view('firm-admin.billing', [
            'firm' => $firm,
            'summary' => $summary,
        ]);
    }
}
