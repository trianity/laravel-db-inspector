<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Database;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class AnalysisConnection
{
    private readonly string $name;

    private ?Connection $connection = null;

    public function __construct(?string $connectionName = null)
    {
        $this->name = $connectionName ?: DB::getDefaultConnection();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function connection(): Connection
    {
        if ($this->connection === null) {
            $this->connection = DB::connection($this->name);
        }

        return $this->connection;
    }

    public function driver(): string
    {
        $configDriver = (string) ($this->config()['driver'] ?? '');

        return $configDriver !== '' ? $configDriver : $this->connection()->getDriverName();
    }

    public function prefix(): string
    {
        $configPrefix = (string) ($this->config()['prefix'] ?? '');

        return $configPrefix !== '' ? $configPrefix : $this->connection()->getTablePrefix();
    }

    public function database(): ?string
    {
        $database = $this->config()['database'] ?? null;

        return is_string($database) ? $database : $this->connection()->getDatabaseName();
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return (array) config("database.connections.{$this->name}", []);
    }

    /**
     * @return array<int, string>
     */
    public function physicalTableNames(): array
    {
        $connection = $this->connection();

        if (DatabaseDriver::isMySqlCompatible($this->driver())) {
            $tables = $connection->select('SHOW TABLES');

            return array_values(array_filter(array_map(
                static function (object $table): ?string {
                    $values = array_values(get_object_vars($table));

                    return isset($values[0]) ? (string) $values[0] : null;
                },
                $tables
            )));
        }

        if (DatabaseDriver::isPostgreSql($this->driver())) {
            $tables = $connection->select("
                SELECT tablename
                FROM pg_catalog.pg_tables
                WHERE schemaname = 'public'
            ");

            return array_values(array_filter(array_map(
                static fn (object $table): ?string => isset($table->tablename) ? (string) $table->tablename : null,
                $tables
            )));
        }

        return [];
    }

    public function tableCount(): int
    {
        return count($this->physicalTableNames());
    }

    public function databaseName(): string
    {
        return (string) ($this->database() ?? '');
    }
}
