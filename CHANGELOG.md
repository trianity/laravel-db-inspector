# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [1.0.0] - 2026-07-17

### Added

- `AnalysisConnection` for centralized analysis connection handling.
- `DatabaseDriver` and `TableNameNormalizer` helpers.
- Explicit zero-table preflight failure handling.
- Pest 4 and Orchestra Testbench 11 package test coverage.
- Package boot, command registration, route registration, connection, prefix, and no-tables regression tests.
- Composer metadata and publishing checks for repository readiness.

### Changed

- Narrowed the supported runtime to PHP 8.4+ and Laravel 13.
- Package rename to `trianity/laravel-db-inspector`.
- Namespace and service provider naming aligned with the new package identity.
- Artisan command renamed to `db-inspector:analyze`.
- Connection handling centralized across the analyzer and checks.
- Check classes now use the shared analysis connection API.
- Logical and physical table names are kept separate during analysis.
- Web route registration is now guarded by explicit config flags.

### Fixed

- MariaDB is treated as MySQL-compatible during analysis.
- Connection name and database driver name are no longer conflated.
- Laravel table prefix is no longer applied twice.
- Zero discovered tables now fails analysis instead of reporting a false success.
- Larastan issues from the previous task cycle were resolved.

### Security

- Web route remains disabled by default.
- Web route only registers after explicit config opt-in.
- Middleware is config-driven and must be chosen by the host application.
- Installation guidance uses `--dev` to keep the package out of production runtime installs.

### Removed

- Old `dbstan:analyze` references in runtime code and user-facing documentation.

## [1.0.8] - 2026-04-06

### Added

- PostgreSQL support.

### Changed

- Released version 1.0.8.

## [1.0.5] - 2026-03-09

### Fixed

- Added logic to show a message when the database is not selected.
- Updated error color naming by type.
- Added a loader while scanning the database.

## [1.0.4] - 2026-03-09

### Fixed

- Minor bug fixes.
- Updated README.md.

## [1.0.3] - 2026-02-26

### Added

- URL-based logic implemented.
- Configuration files added.
- Commands can be run based on mode.

### Changed

- Refactored logic to select between command or URL mode.

### Fixed

- No critical fixes in this release.

## [1.0.2] - 2026-02-13

### Fixed

- Minor bug fixes.

## [1.0.1] - 2026-02-13

### Added

- Initial release of orphan risk check.

[Unreleased]: https://github.com/trianity/laravel-db-inspector/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/trianity/laravel-db-inspector/releases/tag/v1.0.0