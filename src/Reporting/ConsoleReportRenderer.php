<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Reporting;

use Trianity\LaravelDbInspector\Analysis\AnalysisResult;
use Trianity\LaravelDbInspector\Analysis\Finding;

final class ConsoleReportRenderer
{
    public function render(AnalysisResult $result): string
    {
        $lines = [];
        $context = $result->context;

        $lines[] = '';
        $lines[] = 'Database Inspector Analysis Context';
        $lines[] = str_repeat('-', 40);
        $lines[] = sprintf('Connection  : %s', $context->connectionName);
        $lines[] = sprintf('Driver      : %s', $context->driver);
        $lines[] = sprintf('Database    : %s', $context->database);
        $lines[] = sprintf('Host        : %s', $context->host ?? 'N/A');
        $lines[] = sprintf('Port        : %s', (string) ($context->port ?? 'N/A'));
        $lines[] = sprintf('Tables      : %d', $context->tableCount);
        $lines[] = sprintf('Prefix      : %s', $context->prefix);
        $lines[] = sprintf('Environment : %s', $context->environment);
        $lines[] = str_repeat('-', 40);
        $lines[] = '';
        $lines[] = 'Starting Database Inspector database structure analysis...';
        $lines[] = sprintf('Checks executed: %d', $result->checksExecuted());
        $lines[] = sprintf('Findings        : %d', $result->findingCount());
        $lines[] = sprintf('Technical errors: %d', $result->technicalErrorCount());
        $lines[] = '';

        if ($result->findings === []) {
            $lines[] = 'No major database design issues found!';
        } else {
            $grouped = [];

            foreach ($result->findings as $finding) {
                $grouped[$finding->category][$finding->checkName][] = $finding;
            }

            foreach ($grouped as $category => $checks) {
                $lines[] = strtoupper(str_replace('_', ' ', $category));

                foreach ($checks as $checkName => $findings) {
                    $lines[] = '  '.$checkName;

                    foreach ($findings as $index => $finding) {
                        $lines[] = sprintf(
                            '    %3d. %s %s',
                            $index + 1,
                            $this->severityLabel($finding->severity->value),
                            $this->formatFindingLine($finding)
                        );
                    }
                }
            }
        }

        if ($result->technicalErrors !== []) {
            $lines[] = '';
            $lines[] = 'Technical errors encountered during analysis:';

            foreach ($result->technicalErrors as $error) {
                $lines[] = sprintf('- %s: %s', $error->checkName, $error->message);
            }
        }

        return implode("\n", $lines)."\n";
    }

    private function severityLabel(string $severity): string
    {
        return match ($severity) {
            'error' => '[ERROR]',
            'warning' => '[WARNING]',
            default => '[INFO]',
        };
    }

    private function formatFindingLine(Finding $finding): string
    {
        $parts = [$finding->message];

        if ($finding->table !== null) {
            $parts[] = 'Table: '.$finding->table;
        }

        if ($finding->column !== null) {
            $parts[] = 'Column: '.$finding->column;
        }

        if ($finding->recommendation !== null) {
            $parts[] = $finding->recommendation;
        }

        return implode(' | ', $parts);
    }
}
