<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('registers the analyze command', function (): void {
    expect(Artisan::all())
        ->toHaveKey('db-inspector:analyze');
});
