<?php

namespace App\Providers;

use App\Auth\FirmAwareUserProvider;
use App\Domain\Calendar\CalendarSyncProvider;
use App\Domain\Calendar\NullCalendarSyncProvider;
use App\Domain\Firm\FirmContext;
use App\Domain\Scheduling\FirmTime;
use App\Models\FirmSetting;
use App\Policies\FirmPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FirmContext::class);
        $this->app->singleton(FirmTime::class);
        $this->app->bind(CalendarSyncProvider::class, NullCalendarSyncProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('firm-eloquent', function ($app, array $config) {
            return new FirmAwareUserProvider($app['hash'], $config['model']);
        });

        // FirmSetting is an alias of Firm; policy auto-discovery only maps Firm.
        Gate::policy(FirmSetting::class, FirmPolicy::class);

        Blade::directive('firmTime', function (string $expression): string {
            return "<?php echo e(app(\\App\\Domain\\Scheduling\\FirmTime::class)->format($expression)); ?>";
        });
    }
}
