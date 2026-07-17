<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Checks;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Trianity\LaravelDbInspector\Analysis\Finding;
use Trianity\LaravelDbInspector\Analysis\Severity;
use Trianity\LaravelDbInspector\Contracts\DatabaseCheck;
use Trianity\LaravelDbInspector\Database\AnalysisConnection;

abstract class BaseCheck implements DatabaseCheck
{
    protected array $config;

    protected AnalysisConnection $analysisConnection;

    protected const RULE_ID = '';

    public function __construct()
    {
        $this->config = (array) config('laravel-db-inspector', []);
        $this->analysisConnection = new AnalysisConnection(
            $this->config['connection'] ?? null
        );
    }

    protected function connection(): Connection
    {
        return $this->analysisConnection->connection();
    }

    protected function wrapIdentifier(string $identifier): string
    {
        return $this->analysisConnection->wrapIdentifier($identifier);
    }

    protected function wrapTable(string $table): string
    {
        return $this->analysisConnection->wrapTable($table);
    }

    protected function table(string $logicalName): Builder
    {
        return $this->connection()->table($logicalName);
    }

    /**
     * @return array<int, object>
     */
    protected function select(string $query, array $bindings = []): array
    {
        return $this->connection()->select($query, $bindings);
    }

    protected function driver(): string
    {
        return $this->analysisConnection->driver();
    }

    protected function databaseName(): string
    {
        return $this->analysisConnection->databaseName();
    }

    protected function prefix(): string
    {
        return $this->analysisConnection->prefix();
    }

    public function ruleId(): string
    {
        return static::RULE_ID !== ''
            ? static::RULE_ID
            : CheckRegistry::ruleIdFor(static::class);
    }

    /**
     * @param  array<string, bool|int|float|string|null>  $metadata
     */
    protected function finding(
        string $message,
        Severity $severity = Severity::Warning,
        ?string $table = null,
        ?string $column = null,
        ?string $recommendation = null,
        array $metadata = [],
    ): Finding {
        return new Finding(
            ruleId: $this->ruleId(),
            checkName: $this->name(),
            category: $this->category(),
            severity: $severity,
            message: $message,
            table: $table,
            column: $column,
            recommendation: $recommendation,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $issues
     * @return list<Finding>
     */
    protected function legacyFindings(array $issues): array
    {
        $findings = [];

        foreach ($issues as $issueType => $checks) {
            if (! is_array($checks)) {
                continue;
            }

            foreach ($checks as $checkName => $messages) {
                $messages = is_iterable($messages) ? $messages : [$messages];

                foreach ($messages as $message) {
                    if (! is_string($message)) {
                        continue;
                    }

                    [$severity, $cleanMessage] = $this->extractSeverity($message);
                    [$table, $column] = $this->extractTarget($cleanMessage);
                    $recommendation = $this->extractRecommendation($cleanMessage);

                    $findings[] = new Finding(
                        ruleId: $this->ruleId(),
                        checkName: $this->name(),
                        category: $this->category(),
                        severity: $severity,
                        message: $this->stripSeverityTag($cleanMessage),
                        table: $table,
                        column: $column,
                        recommendation: $recommendation,
                        metadata: [
                            'issue_type' => $issueType,
                            'legacy_check' => (string) $checkName,
                        ],
                    );
                }
            }
        }

        return $findings;
    }

    private function stripSeverityTag(string $message): string
    {
        return trim((string) preg_replace('/^\[[^\]]+\]\s*/', '', $message));
    }

    /**
     * @return array{0: Severity, 1: string}
     */
    private function extractSeverity(string $message): array
    {
        if (preg_match('/^\[([^\]]+)\]/', $message, $matches) !== 1) {
            return [Severity::Warning, $message];
        }

        $label = strtolower(trim($matches[1]));

        return [
            match (true) {
                str_contains($label, 'error'),
                str_contains($label, 'risk') => Severity::Error,
                str_contains($label, 'info'),
                str_contains($label, 'best practice') => Severity::Info,
                default => Severity::Warning,
            },
            $message,
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function extractTarget(string $message): array
    {
        if (preg_match("/'([^']+)\\.([^']+)'/", $message, $matches) === 1) {
            return [$matches[1], $matches[2]];
        }

        if (preg_match("/'([^']+)' table/i", $message, $matches) === 1) {
            return [$matches[1], null];
        }

        if (preg_match("/'([^']+)' column/i", $message, $matches) === 1) {
            return [null, $matches[1]];
        }

        return [null, null];
    }

    private function extractRecommendation(string $message): ?string
    {
        foreach ([' Consider ', ' Use ', ' Add ', ' Review ', ' Standardize ', ' Keep ', ' Ensure ', ' Promote ', ' Avoid '] as $needle) {
            $position = stripos($message, $needle);

            if ($position !== false && $position > 0) {
                return trim(substr($message, $position));
            }
        }

        return null;
    }
}
