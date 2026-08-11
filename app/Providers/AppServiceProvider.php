<?php

namespace App\Providers;

use App\Auth\DualLegacyPasswordUserProvider;
use App\Auth\LegacyPasswordUserProvider;
use App\Console\Commands\ExportDatabaseStructureCommand;
use App\Database\CustomSqlServerConnector;
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
        // Fix: pdo_sqlsrv 5.13.0+ di PHP 8.5 tidak mendukung
        // PDO::ATTR_STRINGIFY_FETCHES yang di-set default oleh Laravel,
        // sehingga login (query ke SQL Server) gagal dengan
        // SQLSTATE[IMSSP]: An invalid attribute was designated on the PDO object.
        $this->app->bind('db.connector.sqlsrv', CustomSqlServerConnector::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        if (! $this->app->runningInConsole()) {
            $this->extendExecutionTimeForReportRequests();
        }

        Auth::provider('legacy-eloquent', static function ($app, array $config) {
            return new LegacyPasswordUserProvider($app['hash'], $config['model']);
        });

        Auth::provider('dual-legacy-eloquent', static function ($app, array $config) {
            return new DualLegacyPasswordUserProvider($app['hash'], $config['model']);
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

        if (! $request->is('reports/*') && ! $request->is('api/reports/*') && ! $request->is('dashboard/*') && ! $request->is('api/internal/ascends/*')) {
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
