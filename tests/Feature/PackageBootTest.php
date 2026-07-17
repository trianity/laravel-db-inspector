<?php

declare(strict_types=1);
use Trianity\LaravelDbInspector\LaravelDbInspectorServiceProvider;

it('boots the package service provider', function (): void {
    expect(app()->getProvider(LaravelDbInspectorServiceProvider::class))
        ->not->toBeNull();
});
