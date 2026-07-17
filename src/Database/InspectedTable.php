<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Database;

final readonly class InspectedTable
{
    /**
     * @param  array<int|string, mixed>  $columns
     * @param  array<int|string, mixed>  $indexes
     */
    public function __construct(
        public string $logicalName,
        public string $physicalName,
        public array $columns,
        public array $indexes,
    ) {}
}
