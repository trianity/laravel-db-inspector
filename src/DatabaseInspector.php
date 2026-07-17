<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector;

use Trianity\LaravelDbInspector\Analysis\AnalysisContext;
use Trianity\LaravelDbInspector\Analysis\AnalysisResult;
use Trianity\LaravelDbInspector\Analysis\Finding;
use Trianity\LaravelDbInspector\Analysis\TechnicalError;
use Trianity\LaravelDbInspector\Checks\CheckRegistry;
use Trianity\LaravelDbInspector\Database\AnalysisConnection;
use Trianity\LaravelDbInspector\Database\DatabaseDriver;
use Trianity\LaravelDbInspector\Database\TableNameNormalizer;

class DatabaseInspector
{
    /**
     * @var array<int, object>
     */
    protected array $checks = [];

    private readonly AnalysisConnection $analysisConnection;

    private readonly TableNameNormalizer $tableNameNormalizer;

    public function __construct(?AnalysisConnection $analysisConnection = null)
    {
        $this->analysisConnection = $analysisConnection
            ?? new AnalysisConnection(config('laravel-db-inspector.connection'));
        $this->tableNameNormalizer = new TableNameNormalizer;
        $this->loadChecks();
    }

    public function getContext(): AnalysisContext
    {
        $config = $this->analysisConnection->config();

        return new AnalysisContext(
            connectionName: $this->analysisConnection->name(),
            driver: $this->analysisConnection->driver(),
            database: $this->analysisConnection->databaseName(),
            host: isset($config['host']) ? (string) $config['host'] : null,
            port: $config['port'] ?? null,
            tableCount: $this->analysisConnection->tableCount(),
            prefix: $this->analysisConnection->prefix(),
            environment: app()->environment(),
        );
    }

    public function getPreflightError(): ?string
    {
        $connectionName = $this->analysisConnection->name();
        $driver = $this->analysisConnection->driver();
        $databaseName = $this->analysisConnection->databaseName();
        $tableCount = $this->analysisConnection->tableCount();
        $migrationTableName = $this->resolveMigrationTableName();

        if ($databaseName === '') {
            return 'Database is not configured.';
        }

        if ($tableCount === 0) {
            return sprintf(
                'No database tables were discovered for connection [%s] using driver [%s] and database [%s]. 0 tables discovered. Analysis was not performed.',
                $connectionName,
                $driver,
                $databaseName
            );
        }

        try {
            $this->analysisConnection->connection()->getPdo();
        } catch (\Throwable) {
            return 'Database connection failed.';
        }

        if (! $this->analysisConnection->connection()->getSchemaBuilder()->hasTable($migrationTableName)) {
            return 'Run migrations first.';
        }

        if ($this->analysisConnection->connection()->table($migrationTableName)->count() === 0) {
            return 'No migrations found.';
        }

        return null;
    }

    public function inspect(): AnalysisResult
    {
        $context = $this->getContext();

        if ($this->getPreflightError()) {
            return new AnalysisResult($context, [], [], array_fill(0, count($this->checks), 'preflight'));
        }

        $schema = $this->extractSchema();
        $findings = [];
        $technicalErrors = [];

        foreach ($this->checks as $check) {
            $ruleId = $check->ruleId();
            $checkName = $check->name();

            try {
                $issues = array_filter($check->run($schema));
            } catch (\Throwable $throwable) {
                $technicalErrors[] = new TechnicalError(
                    ruleId: $ruleId,
                    checkName: $checkName,
                    message: $throwable->getMessage(),
                    exceptionClass: $throwable::class,
                );

                continue;
            }

            foreach ($issues as $issue) {
                if (! $issue instanceof Finding) {
                    throw new \UnexpectedValueException(sprintf(
                        'Check [%s] returned a non-Finding payload.',
                        $checkName
                    ));
                }

                $findings[] = $issue;
            }
        }

        return new AnalysisResult(
            $context,
            $findings,
            $technicalErrors,
            array_map(static fn (object $check): string => $check->ruleId(), $this->checks)
        );
    }

    protected function resolveMigrationTableName(): string
    {
        $config = config('database.migrations', 'migrations');

        return is_array($config)
            ? ($config['table'] ?? 'migrations')
            : $config;
    }

    protected function loadChecks(): void
    {
        foreach (CheckRegistry::classes() as $class) {
            $this->checks[] = app($class);
        }
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    public function analyze(): array
    {
        $issues = [];

        foreach ($this->inspect()->findings as $finding) {
            $issues[$finding->category][$finding->checkName][] = $finding->message;
        }

        return $issues;
    }

    /**
     * @return array<string, array{name: string, physical_name: string, columns: list<object>, indexes: list<object>, engine?: string|null, table_rows?: int, table_collation?: string|null, auto_increment?: int|float|null}>
     */
    protected function extractSchema(): array
    {
        $schema = [];
        $prefix = $this->analysisConnection->prefix();
        $driver = $this->analysisConnection->driver();
        $connection = $this->analysisConnection->connection();

        foreach ($this->analysisConnection->physicalTableNames() as $physicalName) {
            $logicalName = $this->tableNameNormalizer->toLogicalName($physicalName, $prefix);
            $wrappedPhysicalName = $this->analysisConnection->wrapIdentifier($physicalName);

            if (DatabaseDriver::isMySqlCompatible($driver)) {
                $columnsRaw = $connection->select("SHOW FULL COLUMNS FROM {$wrappedPhysicalName}");
                $indexes = $connection->select("SHOW INDEX FROM {$wrappedPhysicalName}");
            } elseif (DatabaseDriver::isPostgreSql($driver)) {
                $columnsRaw = $connection->select('
                    SELECT column_name, data_type, is_nullable
                    FROM information_schema.columns
                    WHERE table_name = ?
                ', [$physicalName]);

                $indexes = $connection->select('
                    SELECT indexname, indexdef
                    FROM pg_indexes
                    WHERE tablename = ?
                ', [$physicalName]);
            } else {
                continue;
            }

            $columns = array_map(static function (object $column): object {
                $field = $column->Field
                    ?? $column->column_name
                    ?? $column->name
                    ?? '';

                $type = $column->Type
                    ?? $column->data_type
                    ?? $column->type
                    ?? '';

                $nullable = $column->Null
                    ?? $column->is_nullable
                    ?? null;

                return (object) array_merge((array) $column, [
                    'name' => (string) $field,
                    'physical_name' => (string) $field,
                    'Field' => (string) $field,
                    'column_name' => (string) $field,
                    'type' => strtolower((string) $type),
                    'Type' => (string) $type,
                    'data_type' => strtolower((string) $type),
                    'nullable' => is_bool($nullable)
                        ? $nullable
                        : (is_string($nullable) ? strtoupper($nullable) === 'YES' : false),
                ]);
            }, $columnsRaw);

            $schema[$logicalName] = [
                'name' => $logicalName,
                'physical_name' => $physicalName,
                'columns' => $columns,
                'indexes' => $indexes,
            ];
        }

        return $schema;
    }
}
