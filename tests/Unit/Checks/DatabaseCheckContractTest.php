<?php

declare(strict_types=1);

use Trianity\LaravelDbInspector\Checks\CheckRegistry;
use Trianity\LaravelDbInspector\Contracts\DatabaseCheck;
use Trianity\LaravelDbInspector\Tests\TestCase;

uses(TestCase::class);

it('registers unique database checks with stable identifiers', function (): void {
    $ruleIds = [];

    foreach (CheckRegistry::classes() as $class) {
        $check = app($class);

        expect($check)->toBeInstanceOf(DatabaseCheck::class);
        expect($check->ruleId())->not->toBe('');
        expect($check->name())->not->toBe('');
        expect($check->category())->not->toBe('');

        $ruleIds[] = $check->ruleId();
    }

    expect($ruleIds)->toHaveCount(count(array_unique($ruleIds)));
});
