<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Trianity\LaravelDbInspector\Analysis\AnalysisContext;
use Trianity\LaravelDbInspector\Analysis\AnalysisResult;
use Trianity\LaravelDbInspector\Analysis\Finding;
use Trianity\LaravelDbInspector\Analysis\Severity;
use Trianity\LaravelDbInspector\DatabaseInspector;

function bindFakeInspector(array $findings = [], array $technicalErrors = [], int $checksExecuted = 12): void
{
    app()->instance(DatabaseInspector::class, new class($findings, $technicalErrors, $checksExecuted) extends DatabaseInspector
    {
        public function __construct(
            private readonly array $findings,
            private readonly array $technicalErrors,
            private readonly int $checksExecuted,
        ) {}

        public function getContext(): AnalysisContext
        {
            return new AnalysisContext(
                connectionName: 'crm',
                driver: 'mariadb',
                database: 'kaszanap_laracrmdb',
                host: 'localhost',
                port: 3306,
                tableCount: 77,
                prefix: 'nkt8_',
                environment: 'testing',
            );
        }

        public function getPreflightError(): ?string
        {
            return null;
        }

        public function inspect(): AnalysisResult
        {
            return new AnalysisResult(
                $this->getContext(),
                $this->findings,
                $this->technicalErrors,
                array_fill(0, $this->checksExecuted, 'rule'),
            );
        }
    });
}

function fakeFinding(string $ruleId, string $checkName, string $category, Severity $severity, string $message, ?string $table = null, ?string $column = null, ?string $recommendation = null): Finding
{
    return new Finding(
        ruleId: $ruleId,
        checkName: $checkName,
        category: $category,
        severity: $severity,
        message: $message,
        table: $table,
        column: $column,
        recommendation: $recommendation,
    );
}

it('writes the default markdown report to the application root', function (): void {
    $reportPath = base_path('db-analyse.md');
    File::delete($reportPath);

    bindFakeInspector([
        fakeFinding(
            'performance.missing-indexes',
            'Missing Foreign Key Indexes',
            'performance',
            Severity::Error,
            'users.email_id column looks like FK but has no index',
            'users',
            'email_id',
            'Add an index to support the foreign key',
        ),
    ]);

    try {
        $exitCode = Artisan::call('db-inspector:analyze');

        expect($exitCode)->toBe(0);
        expect(File::exists($reportPath))->toBeTrue();
        expect(File::get($reportPath))->toContain('# Laravel Database Inspection Report');
        expect(File::get($reportPath))->toContain('performance.missing-indexes');
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
        fakeFinding(
            'performance.missing-indexes',
            'Missing Foreign Key Indexes',
            'performance',
            Severity::Error,
            'users.email_id column looks like FK but has no index',
            'users',
            'email_id',
        ),
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
        fakeFinding(
            'performance.missing-indexes',
            'Missing Foreign Key Indexes',
            'performance',
            Severity::Error,
            'users.email_id column looks like FK but has no index',
            'users',
            'email_id',
        ),
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

it('renders structured findings with numeric ordering', function (): void {
    bindFakeInspector([
        fakeFinding(
            'performance.missing-indexes',
            'Missing Foreign Key Indexes',
            'performance',
            Severity::Error,
            'users.email_id column looks like FK but has no index',
            'users',
            'email_id',
        ),
        fakeFinding(
            'performance.missing-indexes',
            'Missing Foreign Key Indexes',
            'performance',
            Severity::Warning,
            'users.profile_id column looks like FK but has no index',
            'users',
            'profile_id',
        ),
    ]);

    $exitCode = Artisan::call('db-inspector:analyze', [
        '--no-report' => true,
    ]);

    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('[ERROR]');
    expect($output)->toContain('[WARNING]');
    expect($output)->toContain('1.');
    expect($output)->toContain('2.');
    expect($output)->toMatch('/Findings\s+: 2/');
});
