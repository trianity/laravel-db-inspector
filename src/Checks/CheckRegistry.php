<?php

declare(strict_types=1);

namespace Trianity\LaravelDbInspector\Checks;

use Trianity\LaravelDbInspector\Checks\Architecture\AuditTrailCheck;
use Trianity\LaravelDbInspector\Checks\Architecture\CharsetCollationConsistencyCheck;
use Trianity\LaravelDbInspector\Checks\Architecture\JsonOveruseCheck;
use Trianity\LaravelDbInspector\Checks\Architecture\StorageEngineCheck;
use Trianity\LaravelDbInspector\Checks\Integrity\CascadingActionsCheck;
use Trianity\LaravelDbInspector\Checks\Integrity\DuplicateRowsCheck;
use Trianity\LaravelDbInspector\Checks\Integrity\ForeignKeyNamingCheck;
use Trianity\LaravelDbInspector\Checks\Integrity\PossibleOrphanRiskCheck;
use Trianity\LaravelDbInspector\Checks\Integrity\UniqueConstraintViolationsCheck;
use Trianity\LaravelDbInspector\Checks\Performance\AutoIncrementRiskCheck;
use Trianity\LaravelDbInspector\Checks\Performance\CompositeIndexRecommendationCheck;
use Trianity\LaravelDbInspector\Checks\Performance\IndexCardinalityAnalysisCheck;
use Trianity\LaravelDbInspector\Checks\Performance\LogTableIndexingCheck;
use Trianity\LaravelDbInspector\Checks\Performance\MissingIndexesCheck;
use Trianity\LaravelDbInspector\Checks\Performance\NullValueRatioCheck;
use Trianity\LaravelDbInspector\Checks\Performance\StatusIndexCheck;
use Trianity\LaravelDbInspector\Checks\Performance\TableSizeAnalysisCheck;
use Trianity\LaravelDbInspector\Checks\Performance\UnboundedGrowthRiskCheck;
use Trianity\LaravelDbInspector\Checks\Structure\BooleanOveruseCheck;
use Trianity\LaravelDbInspector\Checks\Structure\DataTypeAppropriatenessCheck;
use Trianity\LaravelDbInspector\Checks\Structure\EnumOveruseCheck;
use Trianity\LaravelDbInspector\Checks\Structure\LargeTextColumnsCheck;
use Trianity\LaravelDbInspector\Checks\Structure\MissingSoftDeletesCheck;
use Trianity\LaravelDbInspector\Checks\Structure\MissingTimestampsCheck;
use Trianity\LaravelDbInspector\Checks\Structure\MixedDomainColumnsCheck;
use Trianity\LaravelDbInspector\Checks\Structure\NullableOveruseCheck;
use Trianity\LaravelDbInspector\Checks\Structure\PivotTableStructureCheck;
use Trianity\LaravelDbInspector\Checks\Structure\PolymorphicOveruseCheck;
use Trianity\LaravelDbInspector\Checks\Structure\PrimaryKeyPresenceCheck;
use Trianity\LaravelDbInspector\Checks\Structure\RepeatedCommonFieldsCheck;
use Trianity\LaravelDbInspector\Checks\Structure\TooManyColumnsCheck;
use Trianity\LaravelDbInspector\Checks\Structure\WideVarcharsCheck;

final class CheckRegistry
{
    /**
     * @return array<class-string, string>
     */
    public static function ruleIds(): array
    {
        return [
            AuditTrailCheck::class => 'architecture.audit-trail',
            CharsetCollationConsistencyCheck::class => 'architecture.charset-collation-consistency',
            JsonOveruseCheck::class => 'architecture.json-overuse',
            StorageEngineCheck::class => 'architecture.storage-engine',
            CascadingActionsCheck::class => 'integrity.cascading-actions',
            DuplicateRowsCheck::class => 'integrity.duplicate-rows-risk',
            ForeignKeyNamingCheck::class => 'integrity.foreign-key-naming',
            PossibleOrphanRiskCheck::class => 'integrity.possible-orphan-risk',
            UniqueConstraintViolationsCheck::class => 'integrity.unique-constraint-violations',
            AutoIncrementRiskCheck::class => 'performance.auto-increment-risk',
            CompositeIndexRecommendationCheck::class => 'performance.composite-index-recommendation',
            IndexCardinalityAnalysisCheck::class => 'performance.index-cardinality-analysis',
            LogTableIndexingCheck::class => 'performance.log-table-indexing',
            MissingIndexesCheck::class => 'performance.missing-indexes',
            NullValueRatioCheck::class => 'performance.null-value-ratio',
            StatusIndexCheck::class => 'performance.status-index',
            TableSizeAnalysisCheck::class => 'performance.table-size-analysis',
            UnboundedGrowthRiskCheck::class => 'performance.unbounded-growth-risk',
            BooleanOveruseCheck::class => 'architecture.boolean-overuse',
            DataTypeAppropriatenessCheck::class => 'structure.data-type-appropriateness',
            EnumOveruseCheck::class => 'structure.enum-overuse',
            LargeTextColumnsCheck::class => 'structure.large-text-columns',
            MissingSoftDeletesCheck::class => 'structure.missing-soft-deletes',
            MissingTimestampsCheck::class => 'structure.missing-timestamps',
            MixedDomainColumnsCheck::class => 'structure.mixed-domain-columns',
            NullableOveruseCheck::class => 'structure.nullable-overuse',
            PivotTableStructureCheck::class => 'structure.pivot-table-structure',
            PolymorphicOveruseCheck::class => 'architecture.polymorphic-overuse',
            PrimaryKeyPresenceCheck::class => 'structure.primary-key-presence',
            RepeatedCommonFieldsCheck::class => 'structure.repeated-common-fields',
            TooManyColumnsCheck::class => 'structure.too-many-columns',
            WideVarcharsCheck::class => 'structure.wide-varchars',
        ];
    }

    public static function ruleIdFor(string $class): string
    {
        return self::ruleIds()[$class] ?? strtolower(str_replace('\\', '.', $class));
    }

    /**
     * @return list<class-string>
     */
    public static function classes(): array
    {
        return [
            AuditTrailCheck::class,
            CharsetCollationConsistencyCheck::class,
            JsonOveruseCheck::class,
            StorageEngineCheck::class,
            CascadingActionsCheck::class,
            DuplicateRowsCheck::class,
            ForeignKeyNamingCheck::class,
            PossibleOrphanRiskCheck::class,
            UniqueConstraintViolationsCheck::class,
            AutoIncrementRiskCheck::class,
            CompositeIndexRecommendationCheck::class,
            IndexCardinalityAnalysisCheck::class,
            LogTableIndexingCheck::class,
            MissingIndexesCheck::class,
            NullValueRatioCheck::class,
            StatusIndexCheck::class,
            TableSizeAnalysisCheck::class,
            UnboundedGrowthRiskCheck::class,
            BooleanOveruseCheck::class,
            DataTypeAppropriatenessCheck::class,
            EnumOveruseCheck::class,
            LargeTextColumnsCheck::class,
            MissingSoftDeletesCheck::class,
            MissingTimestampsCheck::class,
            MixedDomainColumnsCheck::class,
            NullableOveruseCheck::class,
            PivotTableStructureCheck::class,
            PolymorphicOveruseCheck::class,
            PrimaryKeyPresenceCheck::class,
            RepeatedCommonFieldsCheck::class,
            TooManyColumnsCheck::class,
            WideVarcharsCheck::class,
        ];
    }
}
