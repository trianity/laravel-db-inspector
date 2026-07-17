<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector;

use Illuminate\Support\ServiceProvider;
use Trianity\LaravelDbInspector\Commands\DatabaseInspectorAnalyzeCommand;

class LaravelDbInspectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravel-db-inspector.php',
            'laravel-db-inspector'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/laravel-db-inspector.php' => config_path('laravel-db-inspector.php'),
        ], 'laravel-db-inspector-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                DatabaseInspectorAnalyzeCommand::class,
            ]);
        }

        if (
            config('laravel-db-inspector.enabled', false)
            && config('laravel-db-inspector.web.enabled', false)
        ) {
            $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        }

        $this->loadViewsFrom(__DIR__.'/resources/views', 'laravel-db-inspector');
    }
}
