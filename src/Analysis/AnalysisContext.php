<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Analysis;

final readonly class AnalysisContext
{
    public function __construct(
        public string $connectionName,
        public string $driver,
        public string $database,
        public ?string $host,
        public int|string|null $port,
        public int $tableCount,
        public string $prefix,
        public string $environment,
    ) {}
}
