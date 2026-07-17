<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Reporting;

use Trianity\LaravelDbInspector\Analysis\AnalysisResult;
use Trianity\LaravelDbInspector\Analysis\Finding;

final class MarkdownReportRenderer
{
    public function render(AnalysisResult $result, ?string $generatedAt = null): string
    {
        $generatedAt ??= now()->format('Y-m-d H:i:s');
        $context = $result->context;
        $lines = [
            '# Laravel Database Inspection Report',
            '',
            'Generated: '.$generatedAt,
            'Environment: '.$context->environment,
            '',
            '## Analysis context',
            '',
            '| Property | Value |',
            '|---|---|',
            '| Connection | '.$this->escape($context->connectionName).' |',
            '| Driver | '.$this->escape($context->driver).' |',
            '| Database | '.$this->escape($context->database).' |',
            '| Host | '.$this->escape($context->host ?? 'N/A').' |',
            '| Port | '.$this->escape((string) ($context->port ?? 'N/A')).' |',
            '| Tables | '.$this->escape((string) $context->tableCount).' |',
            '| Prefix | '.$this->escape($context->prefix).' |',
        ];

        $lines[] = '';
        $lines[] = '## Summary';
        $lines[] = '';
        $lines[] = '- Checks executed: '.$result->checksExecuted();
        $lines[] = '- Findings: '.$result->findingCount();
        $lines[] = '- Technical errors: '.$result->technicalErrorCount();
        $lines[] = '';
        $lines[] = '## Findings';
        $lines[] = '';

        if ($result->findings === []) {
            $lines[] = 'No findings were recorded.';
        } else {
            $grouped = [];

            foreach ($result->findings as $finding) {
                $grouped[$finding->category][$finding->checkName][] = $finding;
            }

            foreach ($grouped as $category => $checks) {
                $lines[] = '### '.$this->title($category);
                $lines[] = '';

                foreach ($checks as $checkName => $findings) {
                    $lines[] = '#### '.$checkName;
                    $lines[] = '';

                    foreach ($findings as $finding) {
                        $lines = array_merge($lines, $this->renderFinding($finding));
                    }
                }
            }
        }

        $lines[] = '## Technical errors';
        $lines[] = '';

        if ($result->technicalErrors === []) {
            $lines[] = 'No technical errors occurred.';
        } else {
            foreach ($result->technicalErrors as $error) {
                $lines[] = '- **'.$this->escape($error->checkName).'**: '.$this->escape($error->message);
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @return list<string>
     */
    private function renderFinding(Finding $finding): array
    {
        $lines = [
            '##### '.$this->escape($this->formatTarget($finding)),
            '',
            '- **Rule ID:** '.$this->escape($finding->ruleId),
            '- **Severity:** '.$finding->severity->value,
            '- **Message:** '.$this->escape($finding->message),
        ];

        if ($finding->table !== null) {
            $lines[] = '- **Table:** '.$this->escape($finding->table);
        }

        if ($finding->column !== null) {
            $lines[] = '- **Column:** '.$this->escape($finding->column);
        }

        if ($finding->recommendation !== null) {
            $lines[] = '- **Recommendation:** '.$this->escape($finding->recommendation);
        }

        if ($finding->metadata !== []) {
            $lines[] = '- **Metadata:**';

            $metadata = $finding->metadata;
            ksort($metadata);

            foreach ($metadata as $key => $value) {
                $lines[] = '  - **'.$this->escape($key).'**: '.$this->escape((string) $value);
            }
        }

        $lines[] = '';

        return $lines;
    }

    private function formatTarget(Finding $finding): string
    {
        if ($finding->table !== null && $finding->column !== null) {
            return $finding->table.'.'.$finding->column;
        }

        if ($finding->table !== null) {
            return $finding->table;
        }

        return $finding->checkName;
    }

    private function title(string $value): string
    {
        return ucfirst(str_replace('_', ' ', $value));
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '*', '_', '`', '|'], ['\\\\', '\*', '\_', '\`', '\|'], $value);
    }
}
