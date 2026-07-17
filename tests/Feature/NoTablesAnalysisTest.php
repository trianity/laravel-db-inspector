<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

it('fails analysis when no tables were discovered', function (): void {
    $exitCode = Artisan::call('db-inspector:analyze');

    expect($exitCode)->toBe(Command::FAILURE);
    expect(Artisan::output())->toContain('No database tables were discovered');
});

it('does not report a clean database when no tables were inspected', function (): void {
    $exitCode = Artisan::call('db-inspector:analyze');

    expect($exitCode)->toBe(Command::FAILURE);
    expect(Artisan::output())->not->toContain('No major database design issues found!');
});
