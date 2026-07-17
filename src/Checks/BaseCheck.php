<?php

namespace Trianity\LaravelDbInspector\Checks;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Trianity\LaravelDbInspector\Contracts\CheckInterface;
use Trianity\LaravelDbInspector\Database\AnalysisConnection;

abstract class BaseCheck implements CheckInterface
{
    protected array $config;

    protected AnalysisConnection $analysisConnection;

    public function __construct()
    {
        $this->config = (array) config('laravel-db-inspector', []);
        $this->analysisConnection = new AnalysisConnection(
            $this->config['connection'] ?? null
        );
    }

    protected function connection(): Connection
    {
        return $this->analysisConnection->connection();
    }

    protected function table(string $logicalName): Builder
    {
        return $this->connection()->table($logicalName);
    }

    /**
     * @return array<int, object>
     */
    protected function select(string $query, array $bindings = []): array
    {
        return $this->connection()->select($query, $bindings);
    }

    protected function driver(): string
    {
        return $this->analysisConnection->driver();
    }

    protected function databaseName(): string
    {
        return $this->analysisConnection->databaseName();
    }

    protected function prefix(): string
    {
        return $this->analysisConnection->prefix();
    }
}
