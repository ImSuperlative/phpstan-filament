<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Rules\MakeFieldValidation;

use ImSuperlative\FilamentPhpstan\FieldValidationLevel;
use ImSuperlative\FilamentPhpstan\Support\ModelReflectionHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;

/**
 * Validates aggregate field patterns like `comments_count`, `comments_avg_rating`.
 *
 * Parses the `{relation}_{function}[_{column}]` pattern and checks:
 * - The relation segment exists on the model
 * - At level 2+, the column segment exists on the related model
 */
class AggregateFieldValidator
{
    protected const string IDENTIFIER = 'filamentPhpstan.columnName.aggregateRelation';

    protected const array AGGREGATE_FUNCTIONS = ['avg', 'sum', 'min', 'max', 'count', 'exists'];

    public function __construct(
        protected FieldValidationLevel $level,
        protected ModelReflectionHelper $modelReflectionHelper,
    ) {}

    /**
     * Extract the relation and column parts from an aggregate field name.
     * e.g. "comments_count" → ["comments", null]
     *      "comments_avg_rating" → ["comments", "rating"]
     *
     * @return array{string, ?string}|null
     */
    public function extractAggregateParts(string $fieldName): ?array
    {
        $segments = explode('_', $fieldName);
        if (count($segments) < 2) {
            return null;
        }

        for ($i = count($segments) - 1; $i >= 1; $i--) {
            if (! in_array($segments[$i], self::AGGREGATE_FUNCTIONS, true)) {
                continue;
            }

            $relation = implode('_', array_slice($segments, 0, $i));
            $column = $i < count($segments) - 1
                ? implode('_', array_slice($segments, $i + 1))
                : null;

            return [$relation, $column];
        }

        return null;
    }

    /**
     * @param  array{string, ?string}  $parts  [relation, column]
     * @return list<RuleError>
     *
     * @throws ShouldNotHappenException
     */
    public function validate(array $parts, string $fieldName, string $modelClass, Scope $scope): array
    {
        [$relationPart, $columnPart] = $parts;

        $errors = $this->validateRelation($relationPart, $fieldName, $modelClass, $scope);

        return $errors !== []
            ? $errors
            : $this->validateColumn($relationPart, $columnPart, $fieldName, $modelClass, $scope);
    }

    /**
     * @return list<RuleError>
     *
     * @throws ShouldNotHappenException
     */
    protected function validateRelation(string $relationPart, string $fieldName, string $modelClass, Scope $scope): array
    {
        $result = $this->modelReflectionHelper->isRelationship($modelClass, $relationPart, $scope);

        // Aggregate format is specific — relation must exist at any level
        if ($result === true) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                "'%s' is not a relationship on %s in aggregate field '%s'.",
                $relationPart,
                $modelClass,
                $fieldName,
            ))
                ->identifier(self::IDENTIFIER)
                ->build(),
        ];
    }

    /**
     * Level 2+: validate that the column segment exists on the related model.
     *
     * @return list<RuleError>
     *
     * @throws ShouldNotHappenException
     */
    protected function validateColumn(string $relationPart, ?string $columnPart, string $fieldName, string $modelClass, Scope $scope): array
    {
        if (! $this->level->shouldValidateAggregateColumn() || $columnPart === null) {
            return [];
        }

        $relatedModel = $this->modelReflectionHelper->resolveRelatedModel($modelClass, $relationPart, $scope);
        if ($relatedModel === null || $this->modelReflectionHelper->hasProperty($relatedModel, $columnPart)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                "'%s' does not exist on %s in aggregate field '%s'.",
                $columnPart,
                $relatedModel,
                $fieldName,
            ))
                ->identifier(self::IDENTIFIER)
                ->build(),
        ];
    }
}
