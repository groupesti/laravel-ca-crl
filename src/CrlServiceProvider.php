<?php

declare(strict_types=1);

namespace CA\Crl;

use CA\Crl\Console\Commands\CrlGenerateCommand;
use CA\Crl\Console\Commands\CrlListCommand;
use CA\Crl\Console\Commands\CrlPublishCommand;
use CA\Crl\Contracts\CrlDistributionInterface;
use CA\Crl\Contracts\CrlManagerInterface;
use CA\Crl\Scheduling\CrlAutoGenerate;
use CA\Crl\Services\CrlDistributor;
use CA\Crl\Services\CrlGenerator;
use CA\Crl\Services\CrlManager;
use CA\Key\Contracts\KeyManagerInterface;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CrlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ca-crl.php',
            'ca-crl',
        );

        $this->app->singleton(CrlGenerator::class);

        $this->app->singleton(CrlDistributionInterface::class, CrlDistributor::class);

        $this->app->singleton(CrlManagerInterface::class, function ($app): CrlManager {
            return new CrlManager(
                generator: $app->make(CrlGenerator::class),
                keyManager: $app->make(KeyManagerInterface::class),
                distributor: $app->make(CrlDistributionInterface::class),
            );
        });

        $this->app->alias(CrlManagerInterface::class, 'ca-crl');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/ca-crl.php' => config_path('ca-crl.php'),
            ], 'ca-crl-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'ca-crl-migrations');

            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            $this->commands([
                CrlGenerateCommand::class,
                CrlPublishCommand::class,
                CrlListCommand::class,
            ]);
        }

        $this->registerRoutes();
        $this->registerScheduler();
    }

    private function registerRoutes(): void
    {
        if (!config('ca-crl.routes.enabled', true)) {
            return;
        }

        Route::prefix(config('ca-crl.routes.prefix', 'api/ca/crls'))
            ->middleware(config('ca-crl.routes.middleware', ['api']))
            ->group(__DIR__ . '/../routes/api.php');
    }

    private function registerScheduler(): void
    {
        if (!config('ca-crl.auto_generate', true)) {
            return;
        }

        $this->app->booted(function (): void {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class);
            $frequency = config('ca-crl.schedule_frequency', 'daily');

            $event = $schedule->call($this->app->make(CrlAutoGenerate::class));

            if (method_exists($event, $frequency)) {
                $event->{$frequency}();
            } else {
                $event->daily();
            }
        });
    }
}
