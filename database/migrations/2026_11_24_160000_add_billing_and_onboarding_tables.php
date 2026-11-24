<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'account_type')) {
                $table->string('account_type', 32)->nullable()->after('firm_id');
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 32)->nullable()->after('account_type');
            }
            if (! Schema::hasColumn('users', 'stripe_customer_id')) {
                $table->string('stripe_customer_id')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'onboarding')) {
                $table->json('onboarding')->nullable()->after('stripe_customer_id');
            }
            if (! Schema::hasColumn('users', 'pending_plan')) {
                $table->string('pending_plan', 32)->nullable()->after('onboarding');
            }
            if (! Schema::hasColumn('users', 'pending_interval')) {
                $table->string('pending_interval', 16)->nullable()->after('pending_plan');
            }
        });

        Schema::table('firms', function (Blueprint $table) {
            if (! Schema::hasColumn('firms', 'name')) {
                $table->string('name')->nullable()->after('firm_name');
            }
            if (! Schema::hasColumn('firms', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
        });

        if (Schema::hasTable('legal_cases') && Schema::hasColumn('legal_cases', 'firm_id')) {
            Schema::table('legal_cases', function (Blueprint $table) {
                $table->unsignedBigInteger('firm_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('lawyers') && Schema::hasColumn('lawyers', 'firm_id')) {
            Schema::table('lawyers', function (Blueprint $table) {
                $table->unsignedBigInteger('firm_id')->nullable()->change();
            });
        }

        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('firm_id')->nullable()->constrained()->nullOnDelete();
                $table->string('plan', 32);
                $table->string('interval', 16)->default('monthly');
                $table->string('stripe_customer_id')->nullable();
                $table->string('stripe_subscription_id')->nullable()->unique();
                $table->string('stripe_checkout_session_id')->nullable()->index();
                $table->string('status', 32)->default('incomplete');
                $table->timestamp('current_period_end')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['firm_id', 'status']);
            });
        }

        if (! Schema::hasTable('stripe_webhook_events')) {
            Schema::create('stripe_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_id')->unique();
                $table->string('type');
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sales_inquiries')) {
            Schema::create('sales_inquiries', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('firm_name')->nullable();
                $table->text('message');
                $table->string('plan', 32)->default('pilot');
                $table->string('status', 32)->default('new');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_inquiries');
        Schema::dropIfExists('stripe_webhook_events');
        Schema::dropIfExists('subscriptions');

        Schema::table('firms', function (Blueprint $table) {
            if (Schema::hasColumn('firms', 'owner_id')) {
                $table->dropConstrainedForeignId('owner_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach (['pending_interval', 'pending_plan', 'onboarding', 'stripe_customer_id', 'account_type'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
