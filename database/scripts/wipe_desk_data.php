<?php

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Lawyer;
use App\Models\Lead;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkingHour;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

DB::transaction(function () {
    Appointment::query()->withoutGlobalScopes()->delete();

    if (Schema::hasTable('client_lead_links')) {
        DB::table('client_lead_links')->delete();
    }
    if (Schema::hasTable('client_service')) {
        DB::table('client_service')->delete();
    }
    if (Schema::hasTable('lawyer_service')) {
        DB::table('lawyer_service')->delete();
    }
    if (Schema::hasTable('service_lawyer')) {
        DB::table('service_lawyer')->delete();
    }

    Client::query()->withoutGlobalScopes()->delete();
    Lead::query()->withoutGlobalScopes()->delete();

    WorkingHour::query()->withoutGlobalScopes()->delete();

    $lawyerUserIds = Lawyer::query()->withoutGlobalScopes()->pluck('user_id')->filter()->all();
    Lawyer::query()->withoutGlobalScopes()->delete();

    if ($lawyerUserIds !== []) {
        User::query()
            ->whereIn('id', $lawyerUserIds)
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['Owner', 'Admin']))
            ->delete();
    }

    Service::query()->withoutGlobalScopes()->delete();
});

echo 'Wiped appointments, clients, leads, lawyers, services, working hours.'.PHP_EOL;
echo 'Remaining: appointments='.Appointment::query()->withoutGlobalScopes()->count()
    .' clients='.Client::query()->withoutGlobalScopes()->count()
    .' lawyers='.Lawyer::query()->withoutGlobalScopes()->count()
    .' services='.Service::query()->withoutGlobalScopes()->count()
    .PHP_EOL;
