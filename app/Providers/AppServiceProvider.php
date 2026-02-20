<?php

namespace App\Providers;

use App\Auth\LegacyPasswordUserProvider;
use App\Console\Commands\ExportDatabaseStructureCommand;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

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
        Auth::provider('legacy-eloquent', static function ($app, array $config) {
            return new LegacyPasswordUserProvider($app['hash'], $config['model']);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExportDatabaseStructureCommand::class,
            ]);
        }
    }
}
