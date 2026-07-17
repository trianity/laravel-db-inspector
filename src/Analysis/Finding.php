<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Analysis;

final readonly class Finding
{
    /**
     * @param  array<string, bool|int|float|string|null>  $metadata
     */
    public function __construct(
        public string $ruleId,
        public string $checkName,
        public string $category,
        public Severity $severity,
        public string $message,
        public ?string $table = null,
        public ?string $column = null,
        public ?string $recommendation = null,
        public array $metadata = [],
    ) {}
}
