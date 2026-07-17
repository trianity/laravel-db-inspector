<?php

declare(strict_types=1);

use Trianity\LaravelDbInspector\Database\AnalysisConnection;
use Trianity\LaravelDbInspector\Tests\TestCase;

uses(TestCase::class);

it('uses the configured database connection', function (): void {
    $connection = new AnalysisConnection('crm');

    expect($connection->name())->toBe('crm');
});

it('falls back to the default database connection', function (): void {
    config()->set('database.default', 'sqlite');

    $connection = new AnalysisConnection(null);

    expect($connection->name())->toBe('sqlite');
});

it('does not confuse the connection name with the database driver', function (): void {
    config()->set('database.connections.crm', [
        'driver' => 'mariadb',
        'database' => 'crm_db',
        'prefix' => 'nkt8_',
    ]);

    $connection = new AnalysisConnection('crm');

    expect($connection->config()['driver'])->toBe('mariadb');
    expect($connection->driver())->toBe('mariadb');
    expect($connection->config())->not->toHaveKey('database.connections.mariadb');
});
