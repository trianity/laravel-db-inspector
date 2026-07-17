<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector;

use Illuminate\Support\Facades\File;
use Trianity\LaravelDbInspector\Contracts\CheckInterface;
use Trianity\LaravelDbInspector\Database\AnalysisConnection;
use Trianity\LaravelDbInspector\Database\DatabaseDriver;
use Trianity\LaravelDbInspector\Database\TableNameNormalizer;

class DatabaseInspector
{
    /**
     * @var array<int, CheckInterface>
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

    /**
     * @return array{connection: string, driver: string, database: string, host: string, port: string|int|null, tables: int, prefix: string, environment: string}
     */
    public function getDatabaseInfo(): array
    {
        $config = $this->analysisConnection->config();

        return [
            'connection' => $this->analysisConnection->name(),
            'driver' => $this->analysisConnection->driver(),
            'database' => $this->analysisConnection->databaseName(),
            'host' => (string) ($config['host'] ?? 'N/A'),
            'port' => $config['port'] ?? 'N/A',
            'tables' => $this->analysisConnection->tableCount(),
            'prefix' => $this->analysisConnection->prefix(),
            'environment' => app()->environment(),
        ];
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

    protected function resolveMigrationTableName(): string
    {
        $config = config('database.migrations', 'migrations');

        return is_array($config)
            ? ($config['table'] ?? 'migrations')
            : $config;
    }

    protected function loadChecks(): void
    {
        $path = __DIR__.'/Checks';

        foreach (File::allFiles($path) as $file) {
            $class = $this->getClassFromFile($file);

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
                continue;
            }

            if (in_array(CheckInterface::class, $reflection->getInterfaceNames(), true)) {
                $this->checks[] = app($class);
            }
        }
    }

    protected function getClassFromFile($file): string
    {
        $relative = str_replace(__DIR__.DIRECTORY_SEPARATOR, '', $file->getRealPath());
        $relative = str_replace(['.php', DIRECTORY_SEPARATOR], ['', '\\'], $relative);

        return __NAMESPACE__.'\\'.$relative;
    }

    /**
     * @return array<string, array{name: string, physical_name: string, columns: array<int, object>, indexes: array<int, object>}>
     */
    public function analyze(): array
    {
        if ($this->getPreflightError()) {
            return [];
        }

        $schema = $this->extractSchema();

        $grouped = [
            'structure' => [],
            'integrity' => [],
            'performance' => [],
            'architecture' => [],
        ];

        foreach ($this->checks as $check) {
            $category = $check->category();
            $issues = array_filter($check->run($schema));

            if (! empty($issues)) {
                $grouped[$category] = array_merge($grouped[$category], $issues);
            }
        }

        return $grouped;
    }

    /**
     * @return array<string, array{name: string, physical_name: string, columns: array<int, object>, indexes: array<int, object>}>
     */
    protected function extractSchema(): array
    {
        $schema = [];
        $prefix = $this->analysisConnection->prefix();
        $driver = $this->analysisConnection->driver();
        $connection = $this->analysisConnection->connection();

        foreach ($this->analysisConnection->physicalTableNames() as $physicalName) {
            $logicalName = $this->tableNameNormalizer->toLogicalName($physicalName, $prefix);

            if (DatabaseDriver::isMySqlCompatible($driver)) {
                $columnsRaw = $connection->select("SHOW FULL COLUMNS FROM `{$physicalName}`");
                $indexes = $connection->select("SHOW INDEX FROM `{$physicalName}`");
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
