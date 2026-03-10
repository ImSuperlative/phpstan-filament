<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Rules\ClosureInjection;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class TypedInjectionMap
{
    public function __construct(
        /** @var array<class-string, list<InjectionParameter>> */
        protected readonly array $classMap,
        /** @var array<class-string, list<InjectionParameter>> */
        protected readonly array $typeMap,
        /** @var array<string, list<string>> */
        protected readonly array $methodAdditions,
        protected readonly ReflectionProvider $reflectionProvider,
    ) {}

    /** @return list<InjectionParameter>|null */
    public function resolveForClass(string $className): ?array
    {
        $own = $this->classMap[$className] ?? null;
        $parent = $this->resolveFromParents($className);

        if ($own === null) {
            return $parent;
        }

        if ($parent === null) {
            return $own;
        }

        // Merge: child params override parent params with the same name
        $merged = [];
        foreach ($own as $param) {
            $merged[$param->name] = $param;
        }
        foreach ($parent as $param) {
            if (! isset($merged[$param->name])) {
                $merged[$param->name] = $param;
            }
        }

        return array_values($merged);
    }

    /** @return list<string> */
    public function getMethodAdditions(string $methodName): array
    {
        return $this->methodAdditions[$methodName] ?? [];
    }

    public function findParameter(string $className, string $paramName): ?InjectionParameter
    {
        $params = $this->resolveForClass($className) ?? [];

        return array_values(array_filter($params, fn (InjectionParameter $p) => $p->name === $paramName))[0] ?? null;
    }

    /**
     * Check if a param name is a known injection name in any Filament class.
     */
    public function isReservedName(string $paramName): bool
    {
        $allNames = array_map(fn (InjectionParameter $p) => $p->name, array_merge(...array_values($this->classMap)));
        $allAdditions = array_merge(...array_values($this->methodAdditions));

        return in_array($paramName, [...$allNames, ...$allAdditions], true);
    }

    public function isTypeAllowed(string $className, Type $paramType): bool
    {
        $typeParams = $this->typeMap[$className]
            ?? $this->resolveTypeMapFromParents($className);

        if ($typeParams === null) {
            return false;
        }

        foreach ($typeParams as $typeParam) {
            $allowedType = new ObjectType($typeParam->name);
            if ($allowedType->isSuperTypeOf($paramType)->yes()) {
                return true;
            }
        }

        return false;
    }

    /** @return list<InjectionParameter>|null */
    protected function resolveFromParents(string $className): ?array
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $parents = $this->reflectionProvider->getClass($className)->getParentClassesNames();

        foreach ($parents as $parent) {
            if (isset($this->classMap[$parent])) {
                return $this->classMap[$parent];
            }
        }

        return null;
    }

    /** @return list<InjectionParameter>|null */
    protected function resolveTypeMapFromParents(string $className): ?array
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $parents = $this->reflectionProvider->getClass($className)->getParentClassesNames();

        foreach ($parents as $parent) {
            if (isset($this->typeMap[$parent])) {
                return $this->typeMap[$parent];
            }
        }

        return null;
    }
}
