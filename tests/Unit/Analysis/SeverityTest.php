<?php

declare(strict_types=1);

use Trianity\LaravelDbInspector\Analysis\Severity;

it('exposes the expected severity levels', function (): void {
    expect(Severity::Info->value)->toBe('info');
    expect(Severity::Warning->value)->toBe('warning');
    expect(Severity::Error->value)->toBe('error');
});
