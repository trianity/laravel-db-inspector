<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector;

class DatabaseInspectionResult
{
    /**
     * @param  array{connection: string, driver: string, database: string, host: string, port: string|int|null, tables: int, prefix: string, environment: string}  $context
     * @param  array<string, array<string, array<array-key, string>>>  $groupedIssues
     * @param  array<int, array{check: string, message: string}>  $technicalErrors
     */
    public function __construct(
        private readonly array $context,
        private readonly array $groupedIssues = [],
        private readonly array $technicalErrors = [],
        private readonly int $checksExecuted = 0,
    ) {}

    /**
     * @return array{connection: string, driver: string, database: string, host: string, port: string|int|null, tables: int, prefix: string, environment: string}
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * @return array<string, array<string, array<array-key, string>>>
     */
    public function groupedIssues(): array
    {
        return $this->groupedIssues;
    }

    /**
     * @return array<int, array{check: string, message: string}>
     */
    public function technicalErrors(): array
    {
        return $this->technicalErrors;
    }

    public function checksExecuted(): int
    {
        return $this->checksExecuted;
    }

    public function findingsCount(): int
    {
        $count = 0;

        foreach ($this->groupedIssues as $checks) {
            foreach ($checks as $messages) {
                $count += count($messages);
            }
        }

        return $count;
    }

    public function technicalErrorCount(): int
    {
        return count($this->technicalErrors);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function flatIssues(): array
    {
        $flat = [];

        foreach ($this->groupedIssues as $category => $checks) {
            foreach ($checks as $messages) {
                foreach ($messages as $message) {
                    $flat[$category][] = $message;
                }
            }
        }

        return $flat;
    }

    public function toMarkdown(?string $generatedAt = null): string
    {
        $generatedAt ??= now()->format('Y-m-d H:i:s');

        $lines = [
            '# Laravel Database Inspection Report',
            '',
            'Generated: '.$generatedAt,
            'Environment: '.$this->context['environment'],
            '',
            '## Analysis context',
            '',
            '| Property | Value |',
            '|---|---|',
            '| Connection | '.$this->escapeMarkdownCell($this->context['connection']).' |',
            '| Driver | '.$this->escapeMarkdownCell($this->context['driver']).' |',
            '| Database | '.$this->escapeMarkdownCell($this->context['database']).' |',
            '| Host | '.$this->escapeMarkdownCell($this->context['host']).' |',
            '| Port | '.$this->escapeMarkdownCell((string) $this->context['port']).' |',
            '| Tables | '.$this->escapeMarkdownCell((string) $this->context['tables']).' |',
            '| Prefix | '.$this->escapeMarkdownCell($this->context['prefix']).' |',
        ];

        $lines[] = '';
        $lines[] = '## Summary';
        $lines[] = '';
        $lines[] = '- Checks executed: '.$this->checksExecuted();
        $lines[] = '- Findings: '.$this->findingsCount();
        $lines[] = '- Technical errors: '.$this->technicalErrorCount();
        $lines[] = '';
        $lines[] = '## Findings';
        $lines[] = '';

        if ($this->findingsCount() === 0) {
            $lines[] = 'No findings were recorded.';
        } else {
            foreach ($this->groupedIssues as $category => $checks) {
                $lines[] = '### '.$this->titleCaseLabel($category);
                $lines[] = '';

                foreach ($checks as $checkName => $messages) {
                    $lines[] = '#### '.$checkName;
                    $lines[] = '';

                    foreach ($this->groupMessagesByTarget($messages) as $target => $items) {
                        $lines[] = '##### '.$target;
                        $lines[] = '';

                        foreach ($items as $item) {
                            $lines[] = '- **Severity:** '.$item['severity'];

                            if ($item['column'] !== null) {
                                $lines[] = '- **Column:** '.$item['column'];
                            }

                            $lines[] = '- **Issue:** '.$item['issue'];

                            if ($item['recommendation'] !== null) {
                                $lines[] = '- **Recommendation:** '.$item['recommendation'];
                            }

                            $lines[] = '';
                        }
                    }
                }
            }
        }

        $lines[] = '## Technical errors';
        $lines[] = '';

        if ($this->technicalErrors === []) {
            $lines[] = 'No technical errors occurred.';
        } else {
            foreach ($this->technicalErrors as $error) {
                $lines[] = '- **'.$error['check'].'**: '.$this->sanitizeText($error['message']);
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array<array-key, string>  $messages
     * @return array<string, array<int, array{severity: string, column: string|null, issue: string, recommendation: string|null}>>
     */
    private function groupMessagesByTarget(array $messages): array
    {
        $grouped = [];

        foreach ($messages as $message) {
            $clean = $this->sanitizeText($message);
            $severity = $this->extractSeverity($clean);
            $body = $this->stripSeverityTag($clean);
            [$target, $column] = $this->extractTarget($body);
            [$issue, $recommendation] = $this->splitIssueAndRecommendation($body);

            $grouped[$target][] = [
                'severity' => $severity,
                'column' => $column,
                'issue' => $issue,
                'recommendation' => $recommendation,
            ];
        }

        return $grouped;
    }

    private function sanitizeText(mixed $text): string
    {
        if (is_array($text)) {
            $flattened = [];

            array_walk_recursive($text, static function ($value) use (&$flattened): void {
                if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
                    $flattened[] = (string) $value;

                    return;
                }

                if ($value instanceof \Stringable) {
                    $flattened[] = (string) $value;
                }
            });

            return $this->sanitizeText(implode(' ', $flattened));
        }

        if ($text instanceof \Stringable) {
            return $this->sanitizeText((string) $text);
        }

        if (! is_string($text)) {
            return '';
        }

        return preg_replace('/\x1B\[[0-?]*[ -\/]*[@-~]/', '', $text) ?? $text;
    }

    private function extractSeverity(string $message): string
    {
        if (preg_match('/^\[([^\]]+)\]/', $message, $matches) === 1) {
            return strtolower($matches[1]);
        }

        return 'info';
    }

    private function stripSeverityTag(string $message): string
    {
        return (string) preg_replace('/^\[[^\]]+\]\s*/', '', $message);
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function extractTarget(string $message): array
    {
        foreach (["'", '"', '`'] as $quote) {
            $tableColumnPattern = '/^'.preg_quote($quote, '/').'([^'.preg_quote($quote, '/').']+)\.([^'.preg_quote($quote, '/').']+)'.preg_quote($quote, '/').'\\s+column\\b/i';
            $tablePattern = '/^'.preg_quote($quote, '/').'([^'.preg_quote($quote, '/').']+)'.preg_quote($quote, '/').'\\s+table\\b/i';
            $columnPattern = '/^'.preg_quote($quote, '/').'([^'.preg_quote($quote, '/').']+)'.preg_quote($quote, '/').'\\s+column\\b/i';

            if (preg_match($tableColumnPattern, $message, $matches) === 1) {
                return ['Table: '.$matches[1], $matches[2]];
            }

            if (preg_match($tablePattern, $message, $matches) === 1) {
                return ['Table: '.$matches[1], null];
            }

            if (preg_match($columnPattern, $message, $matches) === 1) {
                return ['Column: '.$matches[1], $matches[1]];
            }
        }

        if (preg_match('/\\bDatabase\\b/i', $message) === 1) {
            return ['Database', null];
        }

        return ['Details', null];
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function splitIssueAndRecommendation(string $message): array
    {
        $message = trim($message);

        foreach ([' Consider ', ' Use ', ' Add ', ' Review ', ' Standardize ', ' Keep ', ' Ensure ', ' Promote ', ' Avoid '] as $needle) {
            $position = stripos($message, $needle);

            if ($position !== false && $position > 0) {
                $issue = trim(substr($message, 0, $position));
                $recommendation = trim(substr($message, $position));

                return [$issue !== '' ? $issue : $message, $recommendation !== '' ? $recommendation : null];
            }
        }

        return [$message, null];
    }

    private function titleCaseLabel(string $label): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $label));
    }

    private function escapeMarkdownCell(string $value): string
    {
        return str_replace('|', '\|', $value);
    }
}
