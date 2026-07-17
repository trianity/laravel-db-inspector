<?php

declare(strict_types=1);
use Trianity\LaravelDbInspector\Database\TableNameNormalizer;

it('removes the configured prefix from a physical table name', function (): void {
    $normalizer = new TableNameNormalizer;

    expect($normalizer->toLogicalName('nkt8_account_contact', 'nkt8_'))->toBe('account_contact');
});

it('leaves an unprefixed table name unchanged', function (): void {
    $normalizer = new TableNameNormalizer;

    expect($normalizer->toLogicalName('account_contact', 'nkt8_'))->toBe('account_contact');
});

it('leaves the table unchanged when the prefix is empty', function (): void {
    $normalizer = new TableNameNormalizer;

    expect($normalizer->toLogicalName('nkt8_account_contact', ''))->toBe('nkt8_account_contact');
});

it('removes only the leading prefix', function (): void {
    $normalizer = new TableNameNormalizer;

    expect($normalizer->toLogicalName('nkt8_nkt8_account_contact', 'nkt8_'))->toBe('nkt8_account_contact');
});

it('does not remove a matching string from the middle of the table name', function (): void {
    $normalizer = new TableNameNormalizer;

    expect($normalizer->toLogicalName('account_nkt8_contact', 'nkt8_'))->toBe('account_nkt8_contact');
});
