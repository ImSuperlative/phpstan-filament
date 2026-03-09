<?php

namespace ImSuperlative\FilamentPhpstan\Rules\MakeFieldValidation;

use ImSuperlative\FilamentPhpstan\Data\SegmentTag;
use ImSuperlative\FilamentPhpstan\FieldValidationLevel;
use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\FieldPathResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\PhpDocAnnotationParser;
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
        protected PhpDocAnnotationParser $phpDocParser,
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

        if ($this->isScopeSkipped($node) || $this->isVirtualField($node)) {
            return [];
        }

        $fieldName = $this->applyStatePrefix($node, $fieldName);
        if ($fieldName === null) {
            return [];
        }

        $aggregateParts = $this->resolveAggregateParts($node, $fieldName);
        if ($aggregateParts !== null) {
            return $this->aggregateFieldValidator->validate($aggregateParts, $fieldName, $modelClass, $scope);
        }

        if (str_contains($fieldName, '.')) {
            return $this->validateDotNotation($fieldName, $modelClass, $scope);
        }

        return $this->level->shouldValidatePlainFields()
            ? $this->validatePlainField($fieldName, $modelClass, $scope)
            : [];
    }

    protected function isScopeSkipped(StaticCall $node): bool
    {
        return $node->getAttribute('filament.scopeSkipped') === true;
    }

    protected function isVirtualField(StaticCall $node): bool
    {
        return $node->getAttribute('filament.virtual') === true;
    }

    /**
     * Prepend nested schema prefix if present. Returns null if prefix
     * exists but the validation level can't resolve intermediate types.
     */
    protected function applyStatePrefix(StaticCall $node, string $fieldName): ?string
    {
        $prefix = $node->getAttribute('filament.statePrefix');

        if ($prefix === null) {
            return $fieldName;
        }

        if (! $this->level->shouldWalkTypedProperties()) {
            return null;
        }

        return $prefix.'.'.$fieldName;
    }

    /**
     * @return array{string, ?string}|null
     */
    protected function resolveAggregateParts(StaticCall $node, string $fieldName): ?array
    {
        /** @var array{string, ?string}|null $override */
        $override = $node->getAttribute('filament.aggregate');

        return $override ?? $this->aggregateFieldValidator->extractAggregateParts($fieldName);
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

        $hasLeaf = $result->remaining === [];
        $intermediateSegments = $hasLeaf
            ? array_slice($result->segments, 0, -1)
            : $result->segments;

        foreach ($intermediateSegments as $segment) {
            if (! $segment->is(SegmentTag::Relation)) {
                return [];
            }
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
        $lastIndex = count($result->segments) - 1;

        foreach ($result->segments as $i => $segment) {
            $isLeaf = $i === $lastIndex && $result->remaining === [];

            if ($isLeaf) {
                if (! $segment->isAny(SegmentTag::Property, SegmentTag::Method)) {
                    return $this->dotSegmentNotFoundError($segment->name, $result->lastResolvedClass() ?? $modelClass, $fieldName);
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

            return $this->segmentNotWalkableError($segment->name, $modelClass, $fieldName);
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
                if ($fieldOverrides[$segment] === ModelReflectionHelper::MODEL_BASE) {
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

                return $this->segmentNotWalkableError($segment, $currentClass, $fieldName);
            }

            $currentClass = $resolved->resolvedClass;
        }

        $leafResult = $this->fieldPathResolver->resolve($leafColumn, $currentClass, $scope);
        $leafSegment = $leafResult->segments[0] ?? null;

        if ($leafSegment === null || ! $leafSegment->isAny(SegmentTag::Property, SegmentTag::Method)) {
            return $this->dotSegmentNotFoundError($leafColumn, $currentClass, $fieldName);
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

        return $this->fieldDoesNotExistError($fieldName, $modelClass);
    }

    /** @return list<RuleError> */
    protected function fieldDoesNotExistError(string $fieldName, string $className): array
    {
        return [$this->buildError(sprintf(
            "'%s' does not exist on %s.",
            $fieldName,
            $className
        ))];
    }

    /** @return list<RuleError> */
    protected function dotSegmentNotFoundError(string $segmentName, string $className, string $fieldName): array
    {
        return [$this->buildError(sprintf(
            "'%s' does not exist on %s in dot-notation field '%s'.",
            $segmentName,
            $className,
            $fieldName
        ))];
    }

    /** @return list<RuleError> */
    protected function segmentNotWalkableError(string $segmentName, string $className, string $fieldName): array
    {
        return [$this->buildError(sprintf(
            "'%s' is not a relationship or typed property on %s in dot-notation field '%s'.",
            $segmentName,
            $className,
            $fieldName
        ))];
    }

    protected function buildError(string $template): RuleError
    {
        return RuleErrorBuilder::message($template)
            ->identifier(self::IDENTIFIER)
            ->build();
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

        $annotations = $this->phpDocParser->readFieldAnnotations($phpDoc->getPhpDocString());
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
