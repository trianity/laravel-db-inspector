<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Database;

final class DatabaseDriver
{
    public static function isMySqlCompatible(string $driver): bool
    {
        return in_array($driver, ['mysql', 'mariadb'], true);
    }

    public static function isPostgreSql(string $driver): bool
    {
        return $driver === 'pgsql';
    }
}
