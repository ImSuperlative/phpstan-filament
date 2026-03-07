<?php

namespace ImSuperlative\FilamentPhpstan\Resolvers;

use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use PHPStan\Reflection\ReflectionProvider;

final class ResourceModelResolver
{
    /** @var array<string, string|null> */
    protected array $cache = [];

    public function __construct(
        protected readonly ReflectionProvider $reflectionProvider,
        protected readonly FilamentClassHelper $filamentClassHelper,
    ) {}

    public function resolve(string $className): ?string
    {
        if (array_key_exists($className, $this->cache)) {
            return $this->cache[$className];
        }

        $result = $this->doResolve($className);
        $this->cache[$className] = $result;

        return $result;
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }

    protected function doResolve(string $className): ?string
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        return $this->resolveFromResource($className)
            ?? $this->resolveFromResourcePage($className)
            ?? $this->resolveFromRelationManager($className);
    }

    protected function resolveFromResource(string $className): ?string
    {
        return $this->filamentClassHelper->isResourceClass($className)
            ? $this->filamentClassHelper->readStaticProperty($className, 'model')
            : null;
    }

    protected function resolveFromResourcePage(string $className): ?string
    {
        if (! $this->filamentClassHelper->isResourcePage($className)) {
            return null;
        }

        $resourceClass = $this->filamentClassHelper->readStaticProperty($className, 'resource');

        return $resourceClass !== null ? $this->resolve($resourceClass) : null;
    }

    protected function resolveFromRelationManager(string $className): ?string
    {
        if (! $this->filamentClassHelper->isRelationManager($className)) {
            return null;
        }

        $resourceClass = $this->filamentClassHelper->readStaticProperty($className, 'resource')
            ?? $this->resolveRelatedResource($className)
            ?? $this->inferResourceFromNamespace($className);

        return $resourceClass !== null ? $this->resolve($resourceClass) : null;
    }

    protected function resolveRelatedResource(string $className): ?string
    {
        return $this->filamentClassHelper->readStaticProperty($className, 'relatedResource');
    }

    protected function inferResourceFromNamespace(string $className): ?string
    {
        $current = $this->reflectionProvider->getClass($className);

        while ($current !== null) {
            $candidate = $this->extractResourceFromClass($current->getName());

            if ($candidate !== null && $this->reflectionProvider->hasClass($candidate)) {
                return $candidate;
            }

            $current = $current->getParentClass();
        }

        return null;
    }

    protected function extractResourceFromClass(string $className): ?string
    {
        $namespace = substr($className, 0, (int) strrpos($className, '\\'));
        $lastSegment = substr($namespace, (int) strrpos($namespace, '\\') + 1);

        return $lastSegment === 'RelationManagers'
            ? substr($namespace, 0, (int) strrpos($namespace, '\\'))
            : null;
    }
}
