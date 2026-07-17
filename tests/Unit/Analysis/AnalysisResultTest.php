<?php

declare(strict_types=1);

use Trianity\LaravelDbInspector\Analysis\AnalysisContext;
use Trianity\LaravelDbInspector\Analysis\AnalysisResult;
use Trianity\LaravelDbInspector\Analysis\Finding;
use Trianity\LaravelDbInspector\Analysis\Severity;
use Trianity\LaravelDbInspector\Analysis\TechnicalError;

function analysisFinding(string $ruleId = 'performance.missing-indexes'): Finding
{
    return new Finding(
        ruleId: $ruleId,
        checkName: 'Missing Foreign Key Indexes',
        category: 'performance',
        severity: Severity::Error,
        message: 'users.email_id column looks like FK but has no index',
        table: 'users',
        column: 'email_id',
    );
}

it('calculates finding count', function (): void {
    $result = new AnalysisResult(
        new AnalysisContext('crm', 'mariadb', 'kaszanap_laracrmdb', 'localhost', 3306, 77, 'nkt8_', 'local'),
        [analysisFinding(), analysisFinding('structure.primary-key-presence')],
        [],
        ['performance.missing-indexes', 'structure.primary-key-presence'],
    );

    expect($result->findingCount())->toBe(2);
    expect($result->checksExecuted())->toBe(2);
});

it('calculates technical error count', function (): void {
    $result = new AnalysisResult(
        new AnalysisContext('crm', 'mariadb', 'kaszanap_laracrmdb', 'localhost', 3306, 77, 'nkt8_', 'local'),
        [analysisFinding()],
        [
            new TechnicalError('performance.missing-indexes', 'Missing Foreign Key Indexes', 'Database connection failed.'),
        ],
        ['performance.missing-indexes'],
    );

    expect($result->technicalErrorCount())->toBe(1);
    expect($result->isSuccessful())->toBeFalse();
});

it('reports successful analysis without technical errors', function (): void {
    $result = new AnalysisResult(
        new AnalysisContext('crm', 'mariadb', 'kaszanap_laracrmdb', 'localhost', 3306, 77, 'nkt8_', 'local'),
        [],
        [],
        [],
    );

    expect($result->isSuccessful())->toBeTrue();
});

it('preserves deterministic executed rule order', function (): void {
    $result = new AnalysisResult(
        new AnalysisContext('crm', 'mariadb', 'kaszanap_laracrmdb', 'localhost', 3306, 77, 'nkt8_', 'local'),
        [analysisFinding()],
        [],
        ['structure.primary-key-presence', 'performance.missing-indexes'],
    );

    expect($result->executedRuleIds)->toBe(['structure.primary-key-presence', 'performance.missing-indexes']);
});
