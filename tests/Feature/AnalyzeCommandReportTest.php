<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Trianity\LaravelDbInspector\DatabaseInspectionResult;
use Trianity\LaravelDbInspector\DatabaseInspector;

function bindFakeInspector(array $groupedIssues = [], array $technicalErrors = [], int $checksExecuted = 12): void
{
    app()->instance(DatabaseInspector::class, new class($groupedIssues, $technicalErrors, $checksExecuted) extends DatabaseInspector
    {
        public function __construct(
            private readonly array $groupedIssues,
            private readonly array $technicalErrors,
            private readonly int $checksExecuted,
        ) {}

        public function getDatabaseInfo(): array
        {
            return [
                'connection' => 'crm',
                'driver' => 'mariadb',
                'database' => 'kaszanap_laracrmdb',
                'host' => 'localhost',
                'port' => 3306,
                'tables' => 77,
                'prefix' => 'nkt8_',
                'environment' => 'testing',
            ];
        }

        public function getPreflightError(): ?string
        {
            return null;
        }

        public function inspect(): DatabaseInspectionResult
        {
            return new DatabaseInspectionResult(
                $this->getDatabaseInfo(),
                $this->groupedIssues,
                $this->technicalErrors,
                $this->checksExecuted,
            );
        }
    });
}

it('writes the default markdown report to the application root', function (): void {
    $reportPath = base_path('db-analyse.md');
    File::delete($reportPath);

    bindFakeInspector([
        'performance' => [
            'Missing Foreign Key Indexes' => [
                "\033[0;37;41m[ERROR]\033[0m 'users.email_id' column looks like FK but has no index",
            ],
        ],
    ]);

    try {
        $exitCode = Artisan::call('db-inspector:analyze');

        expect($exitCode)->toBe(0);
        expect(File::exists($reportPath))->toBeTrue();
        expect(File::get($reportPath))->toContain('# Laravel Database Inspection Report');
        expect(Artisan::output())->toContain('Markdown report written to: '.$reportPath);
    } finally {
        File::delete($reportPath);
    }
});

it('skips report writing when --no-report is used', function (): void {
    $reportPath = base_path('reports/no-report.md');
    File::delete($reportPath);
    File::deleteDirectory(dirname($reportPath));

    bindFakeInspector([
        'performance' => [
            'Missing Foreign Key Indexes' => [
                "\033[0;37;41m[ERROR]\033[0m 'users.email_id' column looks like FK but has no index",
            ],
        ],
    ]);

    try {
        $exitCode = Artisan::call('db-inspector:analyze', [
            '--no-report' => true,
            '--output' => 'reports/no-report.md',
        ]);

        expect($exitCode)->toBe(0);
        expect(File::exists($reportPath))->toBeFalse();
        expect(Artisan::output())->not->toContain('Markdown report written to:');
    } finally {
        File::delete($reportPath);
        File::deleteDirectory(dirname($reportPath));
    }
});

it('allows --output to override a disabled report config', function (): void {
    $reportPath = base_path('reports/override-report.md');
    File::delete($reportPath);
    File::deleteDirectory(dirname($reportPath));

    $previousEnabled = config('laravel-db-inspector.report.enabled', true);
    config()->set('laravel-db-inspector.report.enabled', false);

    bindFakeInspector([
        'performance' => [
            'Missing Foreign Key Indexes' => [
                "\033[0;37;41m[ERROR]\033[0m 'users.email_id' column looks like FK but has no index",
            ],
        ],
    ]);

    try {
        $exitCode = Artisan::call('db-inspector:analyze', [
            '--output' => 'reports/override-report.md',
        ]);

        expect($exitCode)->toBe(0);
        expect(File::exists($reportPath))->toBeTrue();
        expect(File::get($reportPath))->toContain('Missing Foreign Key Indexes');
    } finally {
        config()->set('laravel-db-inspector.report.enabled', $previousEnabled);
        File::delete($reportPath);
        File::deleteDirectory(dirname($reportPath));
    }
});

it('renders associative issue lists without failing on string keys', function (): void {
    bindFakeInspector([
        'performance' => [
            'Missing Foreign Key Indexes' => [
                'missing-index' => "\033[0;37;41m[ERROR]\033[0m 'users.email_id' column looks like FK but has no index",
                'nullable-column' => "\033[0;37;41m[ERROR]\033[0m 'users.profile_id' column is nullable",
            ],
        ],
    ]);

    $exitCode = Artisan::call('db-inspector:analyze', [
        '--no-report' => true,
    ]);

    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toMatch('/1\\.\\s+\\[ERROR\\].*2\\.\\s+\\[ERROR\\]/s');
    expect($output)->toMatch('/Findings\s+: 2/');
});

it('renders nested issue payloads without crashing on arrays', function (): void {
    bindFakeInspector([
        'performance' => [
            'Missing Foreign Key Indexes' => [
                [
                    'severity' => "\033[0;37;41m[ERROR]\033[0m",
                    'issue' => "'users.email_id' column looks like FK but has no index",
                    'recommendation' => 'Add an index to support the foreign key',
                ],
            ],
        ],
    ]);

    $exitCode = Artisan::call('db-inspector:analyze', [
        '--no-report' => true,
    ]);

    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('[ERROR]');
    expect($output)->toContain('users.email_id');
    expect($output)->not->toContain('TypeError');
});
