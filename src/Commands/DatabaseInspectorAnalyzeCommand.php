<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Trianity\LaravelDbInspector\Analysis\AnalysisResult;
use Trianity\LaravelDbInspector\DatabaseInspector;
use Trianity\LaravelDbInspector\Reporting\ConsoleReportRenderer;
use Trianity\LaravelDbInspector\Reporting\MarkdownReportRenderer;

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
        $consoleRenderer = new ConsoleReportRenderer;

        $preflightError = $analyzer->getPreflightError();

        if ($preflightError !== null) {
            $this->newLine();
            $this->error($preflightError);
            $this->line('Database Inspector analysis was skipped until the above issue is fixed.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line($consoleRenderer->render($result));

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

        if (! $result->isSuccessful()) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function shouldSkipReport(): bool
    {
        if ($this->option('no-report')) {
            return true;
        }

        return false;
    }

    private function writeReport(AnalysisResult $result): string
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

        $written = File::put($path, (new MarkdownReportRenderer)->render($result));

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
}
