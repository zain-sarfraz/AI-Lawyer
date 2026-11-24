<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->string('account_type', 32)->default('firm')->after('status');
            $table->string('plan', 32)->nullable()->after('account_type');
            $table->string('billing_interval', 16)->nullable()->after('plan');
            $table->string('subscription_status', 32)->nullable()->after('billing_interval');
            $table->unsignedInteger('max_lawyers')->nullable()->after('subscription_status');
            $table->string('stripe_customer_id')->nullable()->after('max_lawyers');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_subscription_id');
            $table->timestamp('subscribed_at')->nullable()->after('stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->dropColumn([
                'account_type',
                'plan',
                'billing_interval',
                'subscription_status',
                'max_lawyers',
                'stripe_customer_id',
                'stripe_subscription_id',
                'stripe_payment_intent_id',
                'subscribed_at',
            ]);
        });
    }
};
