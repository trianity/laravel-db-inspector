<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Analysis;

final readonly class AnalysisResult
{
    /**
     * @param  list<Finding>  $findings
     * @param  list<TechnicalError>  $technicalErrors
     * @param  list<string>  $executedRuleIds
     */
    public function __construct(
        public AnalysisContext $context,
        public array $findings,
        public array $technicalErrors,
        public array $executedRuleIds,
    ) {}

    public function checksExecuted(): int
    {
        return count($this->executedRuleIds);
    }

    public function findingCount(): int
    {
        return count($this->findings);
    }

    public function technicalErrorCount(): int
    {
        return count($this->technicalErrors);
    }

    public function isSuccessful(): bool
    {
        return $this->technicalErrors === [];
    }
}
