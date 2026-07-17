<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Trianity\LaravelDbInspector\Checks\Performance\NullValueRatioCheck;
use Trianity\LaravelDbInspector\Database\AnalysisConnection;
use Trianity\LaravelDbInspector\DatabaseInspector;
use Trianity\LaravelDbInspector\Tests\TestCase;

uses(TestCase::class);

it('does not apply the Laravel table prefix twice', function (): void {
    $connection = new class extends Connection
    {
        public function __construct() {}

        public function select($query, $bindings = [], $useReadPdo = true, array $fetchUsing = [])
        {
            return match ($query) {
                'SHOW FULL COLUMNS FROM `nkt8_account_contact`' => [
                    (object) [
                        'Field' => 'id',
                        'Type' => 'int',
                        'Null' => 'NO',
                    ],
                ],
                'SHOW INDEX FROM `nkt8_account_contact`' => [],
                default => [],
            };
        }
    };

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

        public function driver(): string
        {
            return 'mysql';
        }

        public function prefix(): string
        {
            return 'nkt8_';
        }

        public function databaseName(): string
        {
            return 'crm_db';
        }

        public function physicalTableNames(): array
        {
            return ['nkt8_account_contact'];
        }

        public function config(): array
        {
            return [
                'driver' => 'mysql',
                'database' => 'crm_db',
                'prefix' => 'nkt8_',
                'host' => '127.0.0.1',
                'port' => 3306,
            ];
        }
    };

    $inspector = new class($analysisConnection) extends DatabaseInspector
    {
        public function schema(): array
        {
            return $this->extractSchema();
        }
    };

    $schema = $inspector->schema();

    expect($schema)->toHaveKey('account_contact');
    expect($schema['account_contact']['physical_name'])->toBe('nkt8_account_contact');

    $check = new class extends NullValueRatioCheck
    {
        public array $capturedTables = [];

        protected function table(string $logicalName): Builder
        {
            $this->capturedTables[] = $logicalName;

            return new class extends Builder
            {
                public function __construct() {}

                public function count($columns = '*'): int
                {
                    return 1;
                }

                public function selectRaw($expression, array $bindings = []): static
                {
                    return $this;
                }

                public function first($columns = ['*']): object
                {
                    return (object) [
                        'total' => 1,
                        'non_null' => 1,
                    ];
                }
            };
        }
    };

    $check->run($schema);

    expect($check->capturedTables)->toContain('account_contact');
    expect($check->capturedTables)->not->toContain('nkt8_account_contact');
});
