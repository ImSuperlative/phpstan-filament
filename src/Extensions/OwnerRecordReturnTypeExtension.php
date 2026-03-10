<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Extensions;

use ImSuperlative\PhpstanFilament\Resolvers\ResourceModelResolver;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class OwnerRecordReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @param  class-string  $className
     */
    public function __construct(
        protected readonly string $className,
        protected readonly bool $enabled,
        protected readonly ResourceModelResolver $resourceModelResolver,
    ) {}

    public function getClass(): string
    {
        return $this->className;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'getOwnerRecord';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        if (! $this->enabled) {
            return null;
        }

        return $this->resolveFromCallerType($methodCall, $scope)
            ?? $this->resolveFromScopeClass($scope);
    }

    protected function resolveFromCallerType(MethodCall $methodCall, Scope $scope): ?Type
    {
        $callerType = $scope->getType($methodCall->var);
        $classNames = $callerType->getObjectClassNames();

        $modelTypes = [];

        foreach ($classNames as $className) {
            $modelClass = $this->resourceModelResolver->resolveResourceModel($className);

            if ($modelClass !== null) {
                $modelTypes[] = new ObjectType($modelClass);
            }
        }

        if ($modelTypes === []) {
            return null;
        }

        return TypeCombinator::addNull(TypeCombinator::union(...$modelTypes));
    }

    protected function resolveFromScopeClass(Scope $scope): ?Type
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return null;
        }

        $modelClass = $this->resourceModelResolver->resolve($classReflection->getName());

        return $modelClass !== null
            ? TypeCombinator::addNull(new ObjectType($modelClass))
            : null;
    }
}
