<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Contracts;

use Trianity\LaravelDbInspector\Analysis\Finding;

interface DatabaseCheck
{
    public function ruleId(): string;

    public function name(): string;

    public function category(): string;

    /**
     * @param  array<string, array{name: string, physical_name: string, columns: list<object>, indexes: list<object>, engine?: string|null, table_rows?: int, table_collation?: string|null, auto_increment?: int|float|null}>  $schema
     * @return list<Finding>
     */
    public function run(array $schema): array;
}
