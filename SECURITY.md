# Security Policy

## Supported versions

During the `0.x` development series, security fixes are provided only for the latest development release.

## Reporting a vulnerability

Please report security issues through GitHub Security Advisories:

https://github.com/trianity/laravel-db-inspector/security/advisories/new

If that channel is not available, open a private report through the repository security workflow rather than disclosing the issue publicly.

## Sensitive database information

This package can process or display sensitive database schema information, including:

- table names;
- column names;
- indexes;
- constraint information;
- size and usage statistics;
- schema patterns that may reveal application structure.

Do not publish screenshots or analysis output without checking whether they expose sensitive information.

Markdown reports written by `db-inspector:analyze` can contain the same schema details as the console output. Treat `db-analyse.md` and any custom report path as sensitive artifacts.

## Web interface safety

- The web interface is disabled by default.
- The route is only registered after explicit configuration opt-in.
- Middleware protection is the host application’s responsibility.
- The package does not automatically choose an authentication guard.
- Public access should not be enabled in production without additional authorization or network restrictions.

## Production usage

- Install the package as a development dependency.
- Use `composer install --no-dev` for production deployments.
- The current analyzer performs read-only inspection queries and does not write application data.
- For production databases, use the least-privilege database account that can still read schema metadata.
- Before running analysis on a production system, verify backups and access controls.

## Disclosure process

We aim to acknowledge the report, investigate the issue, prepare a fix, and coordinate disclosure before public release when practical.
