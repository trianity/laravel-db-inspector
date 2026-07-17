<?php

declare(strict_types=1);

use Trianity\LaravelDbInspector\Analysis\TechnicalError;

it('stores a technical error without sensitive details', function (): void {
    $error = new TechnicalError(
        ruleId: 'performance.null-value-ratio',
        checkName: 'High NULL Value Ratio',
        message: 'Database connection failed.',
        exceptionClass: RuntimeException::class,
    );

    expect($error->ruleId)->toBe('performance.null-value-ratio');
    expect($error->exceptionClass)->toBe(RuntimeException::class);
});
