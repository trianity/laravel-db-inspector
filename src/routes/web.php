<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Trianity\LaravelDbInspector\Http\Controllers\DatabaseInspectorController;

if (
    ! config('laravel-db-inspector.enabled', false)
    || ! config('laravel-db-inspector.web.enabled', false)
) {
    return;
}

Route::prefix(config('laravel-db-inspector.web.prefix', 'db-inspector'))
    ->middleware(config('laravel-db-inspector.web.middleware', ['web']))
    ->group(function (): void {
        Route::get('/', [DatabaseInspectorController::class, 'index'])
            ->name('laravel-db-inspector.index');
    });
