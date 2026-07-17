<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Trianity\LaravelDbInspector\DatabaseInspectionResult;
use Trianity\LaravelDbInspector\DatabaseInspector;

class DatabaseInspectorAnalyzeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db-inspector:analyze
    {--output= : Markdown report path, relative to the Laravel application root unless absolute}
    {--no-report : Do not write a Markdown report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze database structure for design issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $analyzer = app(DatabaseInspector::class);
        $result = $analyzer->inspect();
        $info = $result->context();

        $this->line('');
        $this->info('Database Inspector Analysis Context');
        $this->line(str_repeat('-', 40));

        $this->line("Connection  : {$info['connection']}");
        $this->line("Driver      : {$info['driver']}");
        $this->line("Database    : {$info['database']}");
        $this->line("Host        : {$info['host']}");
        $this->line("Port        : {$info['port']}");
        $this->line("Tables      : {$info['tables']}");
        $this->line("Prefix      : {$info['prefix']}");
        $this->line("Environment : {$info['environment']}");

        $this->line(str_repeat('-', 40));
        $this->line('');

        $preflightError = $analyzer->getPreflightError();

        if ($preflightError !== null) {
            $this->newLine();
            $this->error($preflightError);
            $this->line('Database Inspector analysis was skipped until the above issue is fixed.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Starting Database Inspector database structure analysis...');

        $issues = $result->groupedIssues();
        $this->renderSummary($result);
        $this->renderFindings($issues);

        if ($result->technicalErrorCount() > 0) {
            $this->newLine();
            $this->error('Technical errors encountered during analysis:');

            foreach ($result->technicalErrors() as $error) {
                $this->line(sprintf('- %s: %s', $error['check'], $error['message']));
            }
        }

        $reportPath = null;

        if (! $this->shouldSkipReport()) {
            try {
                $reportPath = $this->writeReport($result);
            } catch (\InvalidArgumentException $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            } catch (\Throwable $exception) {
                $this->error('Failed to write Markdown report: '.$exception->getMessage());

                return self::FAILURE;
            }
        }

        if ($reportPath !== null && $reportPath !== '') {
            $this->newLine();
            $this->info('Markdown report written to: '.$reportPath);
        }

        if ($result->technicalErrorCount() > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function renderSummary(DatabaseInspectionResult $result): void
    {
        $this->line(sprintf('Checks executed: %d', $result->checksExecuted()));
        $this->line(sprintf('Findings        : %d', $result->findingsCount()));
        $this->line(sprintf('Technical errors: %d', $result->technicalErrorCount()));
        $this->newLine();
    }

    /**
     * @param  array<string, array<string, array<array-key, string>>>  $issues
     */
    private function renderFindings(array $issues): void
    {
        if ($issues === [] || $this->countFindings($issues) === 0) {
            $this->info('No major database design issues found!');

            return;
        }

        foreach ($issues as $category => $checks) {
            $this->line(strtoupper(str_replace('_', ' ', $category)));

            foreach ($checks as $checkName => $messages) {
                $this->line('  '.$checkName);

                foreach (array_values($messages) as $index => $issue) {
                    $this->line(sprintf('    %3d. %s', $index + 1, $this->stripAnsi($issue)));
                }
            }

            $this->newLine();
        }
    }

    private function shouldSkipReport(): bool
    {
        if ($this->option('no-report')) {
            return true;
        }

        return false;
    }

    private function writeReport(DatabaseInspectionResult $result): string
    {
        $configuredPath = $this->option('output');

        if (is_string($configuredPath) && trim($configuredPath) !== '') {
            $path = $this->resolveReportPath($configuredPath);
        } elseif (is_string($configuredPath) && trim($configuredPath) === '') {
            throw new \InvalidArgumentException('Report path cannot be empty.');
        } else {
            $enabled = (bool) config('laravel-db-inspector.report.enabled', true);

            if (! $enabled && $configuredPath === null) {
                return '';
            }

            $path = $this->resolveReportPath((string) config('laravel-db-inspector.report.path', 'db-analyse.md'));
        }

        if ($path === '') {
            return '';
        }

        File::ensureDirectoryExists(dirname($path));

        $written = File::put($path, $result->toMarkdown());

        if ($written === false) {
            throw new \RuntimeException('Unable to write report file.');
        }

        return $path;
    }

    private function resolveReportPath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            throw new \InvalidArgumentException('Report path cannot be empty.');
        }

        return $this->isAbsolutePath($path)
            ? $path
            : base_path($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }

    /**
     * @param  array<string, array<string, array<array-key, string>>>  $issues
     */
    private function countFindings(array $issues): int
    {
        $count = 0;

        foreach ($issues as $checks) {
            foreach ($checks as $messages) {
                $count += count($messages);
            }
        }

        return $count;
    }

    private function stripAnsi(mixed $text): string
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

            return $this->stripAnsi(implode(' ', $flattened));
        }

        if ($text instanceof \Stringable) {
            return $this->stripAnsi((string) $text);
        }

        if (! is_string($text)) {
            return '';
        }

        return preg_replace('/\x1B\[[0-?]*[ -\/]*[@-~]/', '', $text) ?? $text;
    }
}
