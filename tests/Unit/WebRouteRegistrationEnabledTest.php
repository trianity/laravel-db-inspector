<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Trianity\LaravelDbInspector\Tests\Unit\WebRouteEnabledTestCase;

uses(WebRouteEnabledTestCase::class);

it('registers the web route when explicitly enabled', function (): void {
    expect(Route::has('laravel-db-inspector.index'))->toBeTrue();
    expect(Route::getRoutes()->getByName('laravel-db-inspector.index'))->not->toBeNull();
});
