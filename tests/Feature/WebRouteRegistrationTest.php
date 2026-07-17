<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('keeps the web route disabled by default', function (): void {
    expect(Route::has('laravel-db-inspector.index'))->toBeFalse();
    expect(Route::getRoutes()->getByName('laravel-db-inspector.index'))->toBeNull();
});
