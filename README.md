# Laravel DB Inspector

A development-focused database schema and design inspection tool for Laravel. It analyzes the actual database structure and database statistics, and keeps the web interface disabled unless you explicitly enable it.

> [!WARNING]
> Laravel DB Inspector is currently experimental.
>
> Version `0.x` is intended for evaluation and development use. The package can
> complete full database analysis and generate structured reports, but several
> rules currently rely on schema-only heuristics and may produce false-positive
> findings.
>
> Findings are review candidates, not definitive proof of database design
> defects. Do not apply schema changes automatically based only on this report.

## Current release

- Current development release: `v0.1.0`
- Stability: experimental

## Stability

Laravel DB Inspector is currently in the `0.x` development series.

Public APIs, rule behavior, report structure, and configuration options may
change between minor `0.x` releases while the analyzer and rule set are being
validated against real Laravel applications.

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

Internally, each check returns structured `Finding` objects, and the command, Markdown report, and web view all consume the same `AnalysisResult` instance.

By default, a Markdown report is written to the Laravel application root as `db-analyse.md`:

```bash
php artisan db-inspector:analyze
```

You can override the path or disable the report:

```bash
php artisan db-inspector:analyze --output=reports/db-analyse.md
php artisan db-inspector:analyze --no-report
```

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
- `DB_INSPECTOR_REPORT_ENABLED`
- `DB_INSPECTOR_REPORT_PATH`

The default configuration lives in `config/laravel-db-inspector.php`, and the web route is only loaded when both the package and the web mode are explicitly enabled.

Report path rules:

- absolute paths are used as-is;
- relative paths are resolved against the host Laravel application's `base_path()`;
- `--output` overrides the config value;
- `--no-report` skips file writing entirely;
- `report.enabled=false` disables automatic writing unless `--output` is passed explicitly.

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

Some checks are deterministic schema observations, while others are heuristic review suggestions. Heuristic findings require application-level validation.

The analyzer currently focuses on these areas:

- Architecture: inspect audit trail, charset/collation consistency, JSON usage, storage engine consistency, boolean flag overuse
- Integrity: flag potential duplicate-row risk, foreign key naming inconsistencies, cascade action mismatches, orphan-risk review candidates, unique constraint issues
- Performance: highlight missing-index review candidates, log-table indexing, status-index opportunities, NULL ratio, table and database size, auto-increment risk, index cardinality, composite-index review candidates, growth-risk review candidates
- Structure: inspect data type appropriateness, enum overuse, large text columns, missing soft deletes or timestamps, mixed domain columns, nullable overuse, pivot structure, polymorphic overuse, missing primary keys, repeated common fields, too many columns, wide varchar fields

Deterministic capabilities include inspecting the live schema, listing actual indexes, reporting actual storage engines, reporting actual collations, reporting actual table sizes, identifying physically missing primary keys, identifying physically missing indexes where the rule has already established a real relation, and generating console and Markdown reports.

## Current limitations

The analyzer currently operates primarily from the live database schema and database statistics. It does not yet combine findings with:

- Laravel migration intent;
- Eloquent model relationships;
- model casts;
- model `$timestamps` configuration;
- `SoftDeletes`;
- polymorphic relation definitions;
- application query patterns;
- domain-specific business rules.

Because of that limited context, some checks are heuristic and may require manual review before any schema change is made.

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
