<?php

namespace App\Providers;

use App\Auth\LegacyPasswordUserProvider;
use App\Console\Commands\ExportDatabaseStructureCommand;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!$this->app->runningInConsole()) {
            $this->extendExecutionTimeForReportRequests();
        }

        Auth::provider('legacy-eloquent', static function ($app, array $config) {
            return new LegacyPasswordUserProvider($app['hash'], $config['model']);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExportDatabaseStructureCommand::class,
            ]);
        }
    }

    private function extendExecutionTimeForReportRequests(): void
    {
        $request = request();

        if (!$request->is('reports/*') && !$request->is('api/reports/*') && !$request->is('dashboard/*')) {
            return;
        }

        $seconds = (int) env('REPORT_MAX_EXECUTION_TIME', 300);
        if ($seconds <= 0) {
            return;
        }

        @ini_set('max_execution_time', (string) $seconds);

        try {
            @set_time_limit($seconds);
        } catch (Throwable) {
            // Ignore when set_time_limit is disabled by runtime configuration.
        }
    }
}
