<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Trianity\LaravelDbInspector\Checks\Performance\NullValueRatioCheck;
use Trianity\LaravelDbInspector\Database\AnalysisConnection;
use Trianity\LaravelDbInspector\DatabaseInspector;
use Trianity\LaravelDbInspector\Tests\TestCase;

uses(TestCase::class);

final class CapturingBuilder extends Builder
{
    public array $capturedExpressions = [];

    public function __construct() {}

    public function count($columns = '*'): int
    {
        return 1;
    }

    public function selectRaw($expression, array $bindings = []): static
    {
        $this->capturedExpressions[] = $expression;

        return $this;
    }

    public function first($columns = ['*']): object
    {
        return (object) [
            'total' => 1,
            'non_null' => 1,
        ];
    }
}

it('does not apply the Laravel table prefix twice', function (): void {
    $connection = new class extends Connection
    {
        public function __construct()
        {
            parent::__construct(new PDO('sqlite::memory:'), '', 'nkt8_');
            $this->setQueryGrammar(new MySqlGrammar($this));
        }

        public function select($query, $bindings = [], $useReadPdo = true, array $fetchUsing = [])
        {
            return match ($query) {
                'SHOW FULL COLUMNS FROM `nkt8_account_contact`' => [
                    (object) [
                        'Field' => 'desc',
                        'Type' => 'int',
                        'Null' => 'YES',
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

    $check = new class($analysisConnection) extends NullValueRatioCheck
    {
        public array $capturedTables = [];

        public ?CapturingBuilder $lastBuilder = null;

        public function __construct(
            private AnalysisConnection $analysisConnectionOverride
        ) {
            parent::__construct();
            $this->analysisConnection = $this->analysisConnectionOverride;
        }

        protected function table(string $logicalName): Builder
        {
            $this->capturedTables[] = $logicalName;

            return $this->lastBuilder = new CapturingBuilder;
        }
    };

    $check->run($schema);

    expect($check->capturedTables)->toContain('account_contact');
    expect($check->capturedTables)->not->toContain('nkt8_account_contact');
    expect($check->lastBuilder?->capturedExpressions[0] ?? null)->toContain('COUNT(`desc`) as non_null');
});
