<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Analysis;

final readonly class TechnicalError
{
    public function __construct(
        public string $ruleId,
        public string $checkName,
        public string $message,
        public ?string $table = null,
        public ?string $column = null,
        public ?string $exceptionClass = null,
    ) {}
}
