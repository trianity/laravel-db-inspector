<?php

declare(strict_types=1);

use Trianity\LaravelDbInspector\DatabaseInspectionResult;

it('renders a clean markdown report from structured findings', function (): void {
    $result = new DatabaseInspectionResult(
        [
            'connection' => 'crm',
            'driver' => 'mariadb',
            'database' => 'kaszanap_laracrmdb',
            'host' => 'localhost',
            'port' => 3306,
            'tables' => 77,
            'prefix' => 'nkt8_',
            'environment' => 'local',
        ],
        [
            'performance' => [
                'Missing Foreign Key Indexes' => [
                    "\033[0;37;41m[ERROR]\033[0m 'users.email_id' column looks like FK but has no index",
                ],
            ],
        ],
        [],
        12,
    );

    $markdown = $result->toMarkdown('2026-07-17 18:30:00');

    expect($markdown)
        ->toContain('# Laravel Database Inspection Report')
        ->toContain('Generated: 2026-07-17 18:30:00')
        ->toContain('| Connection | crm |')
        ->toContain('- Checks executed: 12')
        ->toContain('- Findings: 1')
        ->toContain('- Technical errors: 0')
        ->toContain('### Performance')
        ->toContain('#### Missing Foreign Key Indexes')
        ->toContain('##### Table: users')
        ->toContain('- **Severity:** error')
        ->toContain('- **Column:** email_id')
        ->toContain('column looks like FK but has no index')
        ->not->toContain("\033[0;37;41m");
});
