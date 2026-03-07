<?php

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension;

use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use ImSuperlative\FilamentPhpstan\Rules\ClosureInjection\InjectionMapFactory;
use ImSuperlative\FilamentPhpstan\Rules\ClosureInjection\TypedInjectionMap;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\ClosureType;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

class FilamentClosureTypeExtension implements MethodParameterClosureTypeExtension
{
    protected ?TypedInjectionMap $injectionMap = null;

    /**
     * @param  list<ClosureParameterHandler>  $handlers
     */
    public function __construct(
        protected readonly FilamentClassHelper $filamentClassHelper,
        protected readonly ComponentContextResolver $componentContextResolver,
        protected readonly InjectionMapFactory $injectionMapFactory,
        protected readonly array $handlers,
    ) {}

    public function isMethodSupported(
        MethodReflection $methodReflection,
        ParameterReflection $parameter,
    ): bool {
        $declaringClass = $methodReflection->getDeclaringClass()->getName();

        return $this->filamentClassHelper->isClosureSupported($declaringClass)
            && ! $parameter->getType()->isCallable()->no();
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        ParameterReflection $parameter,
        Scope $scope,
    ): ?Type {
        if ($this->handlers === []) {
            return null;
        }

        $declaringClass = $methodReflection->getDeclaringClass()->getName();
        $modelClass = $this->resolveModelClass($scope);
        $callerClasses = $scope->getType($methodCall->var)->getObjectClassNames();
        $callerClass = $callerClasses[0] ?? null;

        $context = new ClosureHandlerContext(
            scope: $scope,
            methodCall: $methodCall,
            modelClass: $modelClass,
            callerClass: $callerClass,
            declaringClass: $declaringClass,
        );

        // Action path: try action-specific closure typing first
        if ($this->filamentClassHelper->isAction($declaringClass)) {
            return $this->buildActionClosureType($methodCall, $context);
        }

        // Schema/column path
        $closureNode = $this->findClosureNode($methodCall, $parameter);

        return $closureNode !== null
            ? $this->buildTypedParameters($closureNode->params, $context)
            : null;
    }

    protected function findClosureNode(
        MethodCall $methodCall,
        ParameterReflection $parameterReflection,
    ): Closure|ArrowFunction|null {
        $paramName = $parameterReflection->getName();
        $args = $methodCall->getArgs();

        // First: named arg matching the parameter
        $namedArg = array_find($args, fn ($arg) => $arg->name?->toString() === $paramName);

        if ($namedArg !== null) {
            return $this->asClosureNode($namedArg->value);
        }

        // Fallback: first positional closure arg
        return $this->findClosureInArgs($methodCall);
    }

    /**
     * @param  Param[]  $params
     */
    protected function buildTypedParameters(array $params, ClosureHandlerContext $context): ?Type
    {
        return $this->buildClosureType($params, $context);
    }

    protected function buildActionClosureType(MethodCall $methodCall, ClosureHandlerContext $context): ?Type
    {
        $closureNode = $this->findClosureInArgs($methodCall);

        return $closureNode === null
            ? null
            : $this->buildClosureType($closureNode->params, $context);
    }

    protected function findClosureInArgs(MethodCall $methodCall): Closure|ArrowFunction|null
    {
        return array_find_map(
            $methodCall->getArgs(),
            fn ($arg) => $this->asClosureNode($arg->value),
        );
    }

    protected function asClosureNode(Expr $expr): Closure|ArrowFunction|null
    {
        return ($expr instanceof Closure || $expr instanceof ArrowFunction) ? $expr : null;
    }

    /**
     * @param  Param[]  $params
     */
    protected function buildClosureType(array $params, ClosureHandlerContext $context): ?Type
    {
        $changed = false;
        $result = [];

        foreach ($params as $param) {
            if (! $param->var instanceof Variable || ! is_string($param->var->name)) {
                continue;
            }
            // assert($param->var instanceof Variable && is_string($param->var->name));

            $resolvedType = $this->resolveTypeForParam($param, $context);
            $changed = $changed || $resolvedType !== null;
            $result[] = $this->buildParamReflection($param, $resolvedType, $context->scope);
        }

        return $changed ? new ClosureType($result, new MixedType) : null;
    }

    protected function buildParamReflection(Param $param, ?Type $resolvedType, Scope $scope): SimpleParameterReflection
    {
        return new SimpleParameterReflection(
            name: $param->var->name,
            type: $resolvedType ?? ($param->type !== null ? $scope->getFunctionType($param->type, false, false) : new MixedType),
            optional: $param->default !== null,
            variadic: $param->variadic,
        );
    }

    protected function resolveTypeForParam(Param $param, ClosureHandlerContext $context): ?Type
    {
        return $this->resolveParamType($param->var->name, $param->type !== null, $context);
    }

    protected function resolveParamType(string $paramName, bool $hasTypeHint, ClosureHandlerContext $context): ?Type
    {
        $mapType = $this->resolveFromMap($context, $paramName);

        foreach ($this->handlers as $handler) {
            $override = $handler->resolveType($paramName, $hasTypeHint, $context, $mapType);
            if ($override !== null) {
                return $override;
            }
        }

        return ! $hasTypeHint ? $mapType : null;
    }

    protected function resolveFromMap(ClosureHandlerContext $context, string $paramName): ?Type
    {
        if ($context->declaringClass === null) {
            return null;
        }

        return $this->getInjectionMap()->findParameter($context->declaringClass, $paramName)?->type;
    }

    protected function getInjectionMap(): TypedInjectionMap
    {
        return $this->injectionMap ??= $this->injectionMapFactory->create();
    }

    protected function resolveModelClass(Scope $scope): ?string
    {
        return $this->componentContextResolver->resolveModelClassFromScope($scope);
    }
}
