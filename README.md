# Laravel DB Inspector

A development-focused database schema and design inspection tool for Laravel. It analyzes the actual database structure, is production-safe by default, and keeps the web interface disabled unless you explicitly enable it.

## Status

Laravel DB Inspector 1.x is the first stable release line, targeting PHP 8.4 and Laravel 13.

## Supported versions

| Version | Supported |
|---------|-----------|
| 1.x     | Yes       |
| < 1.0   | No        |

## Requirements

- PHP `^8.3`
- Laravel / Illuminate `^13.0`
- MySQL and MariaDB-compatible connections
- PostgreSQL

SQLite appears in the test bootstrap only and does not imply analyzer support.

## Installation

Install it as a development dependency:

```bash
composer require --dev trianity/laravel-db-inspector
```

For production deployments, use the usual no-dev install:

```bash
composer install --no-dev
```

## Usage

Run the Artisan command:

```bash
php artisan db-inspector:analyze
```

The command prints an analysis context and then grouped findings. If no tables are discovered or a preflight check fails, the command exits with a non-zero status.

## Configuration

Publish the configuration file with:

```bash
php artisan vendor:publish --tag=laravel-db-inspector-config
```

Available environment variables:

- `DB_INSPECTOR_ENABLED`
- `DB_INSPECTOR_CONNECTION`
- `DB_INSPECTOR_WEB_ENABLED`
- `DB_INSPECTOR_WEB_PREFIX`

The default configuration lives in `config/laravel-db-inspector.php`, and the web route is only loaded when both the package and the web mode are explicitly enabled.

## Web interface

The web interface is disabled by default.

```php
return [
    'enabled' => env('DB_INSPECTOR_ENABLED', app()->environment(['local', 'testing'])),
    'web' => [
        'enabled' => env('DB_INSPECTOR_WEB_ENABLED', false),
        'prefix' => env('DB_INSPECTOR_WEB_PREFIX', 'db-inspector'),
        'middleware' => ['web'],
    ],
];
```

Important points:

- the route is registered only after explicit opt-in;
- middleware protection is the host application's responsibility;
- the package does not automatically choose an auth guard;
- public access is not recommended in production;
- the output may reveal sensitive database schema information.

## Supported databases

- MySQL
- MariaDB
- PostgreSQL

## What it checks

The analyzer currently focuses on these areas:

- Architecture: audit trail, charset/collation consistency, JSON overuse, storage engine inconsistencies, boolean flag overuse
- Integrity: duplicate row risk, foreign key naming consistency, cascade actions, orphan risk, unique constraint issues
- Performance: missing indexes, log table indexing, status indexes, NULL ratio, table and database size, auto-increment risk, index cardinality, composite index recommendations, growth risk
- Structure: data type appropriateness, enum overuse, large text columns, missing soft deletes or timestamps, mixed domain columns, nullable overuse, pivot structure, polymorphic overuse, missing primary keys, repeated common fields, too many columns, wide varchar fields

## Development

Useful commands:

```bash
composer test
composer test:unit
composer test:feature
composer analyse
composer format
composer format:test
```

The package uses a Testbench workbench environment for bootstrap and package-level verification.

## Security

See [SECURITY.md](./SECURITY.md).

## Attribution

This package is derived from the DBStan project:

- https://github.com/dhanikk/dbstan

The project started from an MIT-licensed base, but it is now independently maintained and significantly redesigned. There is no official relationship with, or endorsement from, the original authors.

## License

MIT License. See [LICENSE](./LICENSE).
