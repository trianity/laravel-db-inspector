<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Database;

final class TableNameNormalizer
{
    public function toLogicalName(string $physicalName, string $prefix): string
    {
        if ($prefix === '' || ! str_starts_with($physicalName, $prefix)) {
            return $physicalName;
        }

        return substr($physicalName, strlen($prefix));
    }
}
