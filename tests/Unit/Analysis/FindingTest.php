<?php

declare(strict_types=1);

use Trianity\LaravelDbInspector\Analysis\Finding;
use Trianity\LaravelDbInspector\Analysis\Severity;

it('stores a structured finding', function (): void {
    $finding = new Finding(
        ruleId: 'structure.data-type-appropriateness',
        checkName: 'Data Type Appropriateness',
        category: 'structure',
        severity: Severity::Warning,
        message: 'Column uses a broader data type than necessary.',
        table: 'users',
        column: 'status',
        recommendation: 'Consider using a constrained string or enum-like representation.',
        metadata: ['current_type' => 'varchar(255)', 'suggested_type' => 'enum'],
    );

    expect($finding->ruleId)->toBe('structure.data-type-appropriateness');
    expect($finding->severity)->toBe(Severity::Warning);
    expect($finding->metadata)->toMatchArray(['current_type' => 'varchar(255)']);
});
