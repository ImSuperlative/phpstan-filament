<?php

namespace ImSuperlative\FilamentPhpstan\Rules\MakeFieldValidation;

use ImSuperlative\FilamentPhpstan\Collectors\AggregateFieldRegistry;
use ImSuperlative\FilamentPhpstan\Collectors\VirtualFieldRegistry;
use ImSuperlative\FilamentPhpstan\Data\SegmentTag;
use ImSuperlative\FilamentPhpstan\FieldValidationLevel;
use ImSuperlative\FilamentPhpstan\Parser\StatePathPrefixVisitor;
use ImSuperlative\FilamentPhpstan\Resolvers\AnnotationReader;
use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\FieldPathResolver;
use ImSuperlative\FilamentPhpstan\Support\AstHelper;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use ImSuperlative\FilamentPhpstan\Support\ModelReflectionHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<StaticCall>
 */
class MakeFieldValidationRule implements Rule
{
    protected const string IDENTIFIER = 'filamentPhpstan.columnName';

    public function __construct(
        protected FieldValidationLevel $level,
        protected ModelReflectionHelper $modelReflectionHelper,
        protected FilamentClassHelper $filamentClassHelper,
        protected ComponentContextResolver $componentContextResolver,
        protected VirtualFieldRegistry $virtualFieldRegistry,
        protected AggregateFieldRegistry $aggregateFieldRegistry,
        protected AnnotationReader $annotationReader,
        protected StatePathPrefixVisitor $statePathPrefixVisitor,
        protected FieldPathResolver $fieldPathResolver,
        protected AggregateFieldValidator $aggregateFieldValidator,
    ) {}

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param  StaticCall  $node
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->shouldProcess($node)) {
            return [];
        }

        $calledClass = $scope->resolveName($node->class);
        $fieldName = AstHelper::firstArgValueAs($node, String_::class)?->value;

        if ($fieldName === null || ! $this->filamentClassHelper->isDisplayComponent($calledClass)) {
            return [];
        }

        $modelClass = $this->componentContextResolver->resolveModelClassFromScope($scope);
        if ($modelClass === null) {
            return [];
        }

        $scopeKey = AstHelper::buildScopeKey($scope);

        // If inside a nested schema, prepend the parent state path prefix.
        // Below level 3 we can't resolve intermediate types, so skip entirely.
        $prefix = $this->statePathPrefixVisitor->lookupPrefix($scope->getFile(), $node->getStartLine());
        if ($prefix !== null) {
            if (! $this->level->shouldWalkTypedProperties()) {
                return [];
            }

            // If the parent component is virtual (->state() / ->view()), skip children entirely
            if ($scopeKey !== null && $this->virtualFieldRegistry->isVirtual($scopeKey, $prefix)) {
                return [];
            }

            $fieldName = $prefix.'.'.$fieldName;
        }

        // Check if scope is skipped (->records() table) or field is virtual (->state() / ->getStateUsing())
        if (
            $scopeKey !== null
            && ($this->virtualFieldRegistry->isScopeSkipped($scopeKey)
                || $this->virtualFieldRegistry->isVirtual($scopeKey, $fieldName))
        ) {
            return [];
        }

        // Aggregate pattern: {relation}_{function}[_{column}], or explicit override from ->counts() / ->avg() etc.
        $aggregateParts = ($scopeKey !== null ? $this->aggregateFieldRegistry->get($scopeKey, $fieldName) : null)
            ?? $this->aggregateFieldValidator->extractAggregateParts($fieldName);
        if ($aggregateParts !== null) {
            return $this->aggregateFieldValidator->validate($aggregateParts, $fieldName, $modelClass, $scope);
        }

        // Dot notation
        if (str_contains($fieldName, '.')) {
            return $this->validateDotNotation($fieldName, $modelClass, $scope);
        }

        // Plain field (level 2+)
        return $this->level->shouldValidatePlainFields()
            ? $this->validatePlainField($fieldName, $modelClass, $scope)
            : [];
    }

    /**
     * @phpstan-assert-if-true Identifier $node->name
     * @phpstan-assert-if-true Name $node->class
     */
    protected function shouldProcess(StaticCall $node): bool
    {
        return $this->level->isEnabled()
            && $node->name instanceof Identifier
            && $node->name->name === 'make'
            && $node->class instanceof Name;
    }

    /**
     * @return list<RuleError>
     */
    protected function validateDotNotation(string $fieldName, string $modelClass, Scope $scope): array
    {
        if ($this->level->shouldWalkTypedProperties()) {
            return $this->validateFullDotPath($fieldName, $modelClass, $scope);
        }

        return $this->validateRelationDotPath($fieldName, $modelClass, $scope);
    }

    /**
     * Level 1-2: walk intermediate segments checking relationships only.
     *
     * @return list<RuleError>
     */
    protected function validateRelationDotPath(string $fieldName, string $modelClass, Scope $scope): array
    {
        $result = $this->fieldPathResolver->resolve($fieldName, $modelClass, $scope);

        // Only check intermediate segments (skip the leaf)
        $intermediateCount = count($result->segments) - 1;
        if ($result->remaining !== []) {
            $intermediateCount = count($result->segments);
        }

        for ($i = 0; $i < $intermediateCount; $i++) {
            $segment = $result->segments[$i];

            if ($segment->is(SegmentTag::Relation)) {
                continue;
            }

            // Not a relation → return no errors (could be cast, property, accessor)
            return [];
        }

        return [];
    }

    /**
     * Level 3: walk the full path resolving each segment to its target class,
     * including typed properties and @filament-field overrides. Validates the leaf.
     *
     * @return list<RuleError>
     */
    protected function validateFullDotPath(string $fieldName, string $modelClass, Scope $scope): array
    {
        $fieldOverrides = $this->resolveFieldOverrides($scope);

        // If there are overrides, we need to walk segment-by-segment to apply them
        if ($fieldOverrides !== []) {
            return $this->validateFullDotPathWithOverrides($fieldName, $modelClass, $fieldOverrides, $scope);
        }

        $result = $this->fieldPathResolver->resolve($fieldName, $modelClass, $scope);

        foreach ($result->segments as $i => $segment) {
            $isLeaf = $i === count($result->segments) - 1 && $result->remaining === [];

            if ($isLeaf) {
                if (! $segment->isAny(SegmentTag::Property, SegmentTag::Method)) {
                    return [
                        RuleErrorBuilder::message(sprintf(
                            "'%s' does not exist on %s in dot-notation field '%s'.",
                            $segment->name,
                            $result->lastResolvedClass() ?? $modelClass,
                            $fieldName,
                        ))
                            ->identifier(self::IDENTIFIER)
                            ->build(),
                    ];
                }

                break;
            }

            // Intermediate: must resolve to a walkable class
            if ($segment->resolvedClass !== null) {
                continue;
            }

            // Can't determine the target class (morphTo, cast, accessor, etc.) — stop walking
            if ($segment->tags !== []) {
                return [];
            }

            return [
                RuleErrorBuilder::message(sprintf(
                    "'%s' is not a relationship or typed property on %s in dot-notation field '%s'.",
                    $segment->name,
                    $modelClass,
                    $fieldName,
                ))
                    ->identifier(self::IDENTIFIER)
                    ->build(),
            ];
        }

        return [];
    }

    /**
     * @param  array<string, string>  $fieldOverrides
     * @return list<RuleError>
     */
    protected function validateFullDotPathWithOverrides(string $fieldName, string $modelClass, array $fieldOverrides, Scope $scope): array
    {
        $segments = explode('.', $fieldName);
        $leafColumn = array_pop($segments);
        $currentClass = $modelClass;

        foreach ($segments as $segment) {
            if (isset($fieldOverrides[$segment])) {
                if ($fieldOverrides[$segment] === 'Illuminate\Database\Eloquent\Model') {
                    return [];
                }
                $currentClass = $fieldOverrides[$segment];

                continue;
            }

            $segmentResult = $this->fieldPathResolver->resolve($segment.'._', $currentClass, $scope);
            $resolved = $segmentResult->segments[0] ?? null;

            if ($resolved === null || $resolved->resolvedClass === null) {
                // Known property/relation but can't resolve target class — stop walking
                if ($resolved !== null && $resolved->tags !== []) {
                    return [];
                }

                return [
                    RuleErrorBuilder::message(sprintf(
                        "'%s' is not a relationship or typed property on %s in dot-notation field '%s'.",
                        $segment,
                        $currentClass,
                        $fieldName,
                    ))
                        ->identifier(self::IDENTIFIER)
                        ->build(),
                ];
            }

            $currentClass = $resolved->resolvedClass;
        }

        $leafResult = $this->fieldPathResolver->resolve($leafColumn, $currentClass, $scope);
        $leafSegment = $leafResult->segments[0] ?? null;

        if ($leafSegment === null || ! $leafSegment->isAny(SegmentTag::Property, SegmentTag::Method)) {
            return [
                RuleErrorBuilder::message(sprintf(
                    "'%s' does not exist on %s in dot-notation field '%s'.",
                    $leafColumn,
                    $currentClass,
                    $fieldName,
                ))
                    ->identifier(self::IDENTIFIER)
                    ->build(),
            ];
        }

        return [];
    }

    /**
     * @return list<RuleError>
     */
    protected function validatePlainField(string $fieldName, string $modelClass, Scope $scope): array
    {
        $result = $this->fieldPathResolver->resolve($fieldName, $modelClass, $scope);
        $segment = $result->segments[0] ?? null;

        if ($segment !== null && $segment->isAny(SegmentTag::Property, SegmentTag::Relation, SegmentTag::Method)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                "'%s' does not exist on %s.",
                $fieldName,
                $modelClass,
            ))
                ->identifier(self::IDENTIFIER)
                ->build(),
        ];
    }

    /**
     * Resolve @filament-field annotation overrides from the current class.
     *
     * @return array<string, string> fieldName => resolvedType
     */
    protected function resolveFieldOverrides(Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        $phpDoc = $classReflection->getResolvedPhpDoc();
        if ($phpDoc === null) {
            return [];
        }

        $annotations = $this->annotationReader->readFieldAnnotations($phpDoc->getPhpDocString());
        $nameScope = $phpDoc->getNullableNameScope();

        $overrides = [];
        foreach ($annotations as $annotation) {
            if ($annotation->fieldName === null) {
                continue;
            }

            $type = $annotation->typeAsString();
            $resolvedType = $nameScope !== null
                ? $nameScope->resolveStringName($type)
                : $type;
            $overrides[$annotation->fieldName] = $resolvedType;
        }

        return $overrides;
    }
}
