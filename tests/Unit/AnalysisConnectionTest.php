<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
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

it('wraps identifiers using the active query grammar', function (): void {
    $pdo = new PDO('sqlite::memory:');
    $connection = new Connection($pdo);
    $connection->setQueryGrammar(new MySqlGrammar($connection));

    $analysisConnection = new class($connection) extends AnalysisConnection
    {
        public function __construct(private Connection $connection)
        {
            parent::__construct('crm');
        }

        public function connection(): Connection
        {
            return $this->connection;
        }
    };

    expect($analysisConnection->wrapIdentifier('desc'))->toBe('`desc`');
    expect($analysisConnection->wrapTable('nkt8_bridgesteps'))->toBe('`nkt8_bridgesteps`');
});
