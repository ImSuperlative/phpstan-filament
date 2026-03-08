<?php

/** @noinspection ClassConstantCanBeUsedInspection */

namespace ImSuperlative\FilamentPhpstan\Resolvers;

use ImSuperlative\FilamentPhpstan\Data\ChainAnalysis;
use ImSuperlative\FilamentPhpstan\Parser\StatePathPrefixVisitor;
use ImSuperlative\FilamentPhpstan\Support\AstHelper;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;

final class StateTypeResolver
{
    public function __construct(
        protected readonly FormComponentStateMap $componentStateMap,
        protected readonly FormComponentTypeNarrower $typeNarrower,
        protected readonly FormComponentChainResolver $chainResolver,
        protected readonly ReflectionProvider $reflectionProvider,
        protected readonly FilamentClassHelper $filamentClassHelper,
        protected readonly StatePathPrefixVisitor $statePathPrefixVisitor,
        protected readonly FieldPathResolver $fieldPathResolver,
        protected readonly int $makeFieldValidation = 0,
    ) {}

    public function resolve(MethodCall $methodCall, Scope $scope, ?string $modelClass): ?Type
    {
        $analysis = $this->analyzeMethodChain($methodCall, $scope);

        return $this->resolveStateType($analysis, $methodCall, $scope, $modelClass);
    }

    protected function analyzeMethodChain(MethodCall $methodCall, Scope $scope): ChainAnalysis
    {
        return $this->chainResolver->resolve($methodCall->var, $scope);
    }

    protected function resolveStateType(ChainAnalysis $analysis, MethodCall $methodCall, Scope $scope, ?string $modelClass): ?Type
    {
        if ($analysis->componentClass === null) {
            return null;
        }

        $baseType = $this->componentStateMap->resolveForClass($analysis->componentClass);

        if ($baseType !== null) {
            return $this->typeNarrower->narrowWithOptions($analysis, $baseType);
        }

        return $this->resolveTableColumnStateType($analysis, $methodCall, $scope, $modelClass);
    }

    protected function resolveTableColumnStateType(ChainAnalysis $analysis, MethodCall $methodCall, Scope $scope, ?string $modelClass): ?Type
    {
        if (! $this->shouldResolveColumnState($analysis, $modelClass)) {
            return null;
        }

        $prefix = $this->resolveStatePathPrefix($methodCall, $scope);
        $fullFieldName = $prefix !== null
            ? $prefix.'.'.$analysis->fieldName
            : $analysis->fieldName;

        if ($this->makeFieldValidation < 3 && str_contains($fullFieldName, '.')) {
            return null;
        }

        return $this->fieldPathResolver->resolve($fullFieldName, $modelClass, $scope)->leafType();
    }

    protected function resolveStatePathPrefix(MethodCall $methodCall, Scope $scope): ?string
    {
        $root = AstHelper::methodChainRoot($methodCall->var);
        $line = $root->getStartLine();

        return $line > 0
            ? $this->statePathPrefixVisitor->lookupPrefix($scope->getFile(), $line)
            : null;
    }

    /**
     * @phpstan-assert-if-true !null $analysis->componentClass
     * @phpstan-assert-if-true !null $analysis->fieldName
     * @phpstan-assert-if-true !null $modelClass
     */
    protected function shouldResolveColumnState(ChainAnalysis $analysis, ?string $modelClass): bool
    {
        return $analysis->componentClass !== null
            && $analysis->fieldName !== null
            && $modelClass !== null
            && $this->filamentClassHelper->isDisplayComponent($analysis->componentClass)
            && $this->reflectionProvider->hasClass($modelClass);
    }
}
