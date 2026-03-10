<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Resolvers;

use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use ImSuperlative\PhpstanFilament\Support\ModelReflectionHelper;
use PHPStan\Reflection\ReflectionProvider;

final class ResourceModelResolver
{
    /** @var array<string, string|null> */
    protected array $cache = [];

    public function __construct(
        protected readonly ReflectionProvider $reflectionProvider,
        protected readonly FilamentClassHelper $filamentClassHelper,
        protected readonly ModelReflectionHelper $modelReflectionHelper,
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

    /**
     * Resolve the parent resource's model (ignoring nested relationship resolution).
     * Used by getOwnerRecord() which always returns the parent resource model.
     */
    public function resolveResourceModel(string $className): ?string
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        // For resource pages (including ManageRelatedRecords), resolve via parent resource
        if ($this->filamentClassHelper->isResourcePage($className)) {
            $resourceClass = $this->filamentClassHelper->readStaticProperty($className, 'resource');

            return $resourceClass !== null ? $this->resolve($resourceClass) : null;
        }

        // For relation managers, same as resolve()
        return $this->resolve($className);
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

        // ManageRelatedRecords pages manage a related model, not the parent resource's model
        if ($this->filamentClassHelper->isManageRelatedRecords($className)) {
            return $this->resolveNestedPageModel($className);
        }

        $resourceClass = $this->filamentClassHelper->readStaticProperty($className, 'resource');

        return $resourceClass !== null ? $this->resolve($resourceClass) : null;
    }

    protected function resolveNestedPageModel(string $className): ?string
    {
        $resourceClass = $this->filamentClassHelper->readStaticProperty($className, 'resource');
        if ($resourceClass === null) {
            return null;
        }

        $parentModel = $this->resolve($resourceClass);
        if ($parentModel === null) {
            return null;
        }

        $relationship = $this->filamentClassHelper->readStaticProperty($className, 'relationship');
        if ($relationship === null) {
            return null;
        }

        return $this->modelReflectionHelper->resolveRelatedModelStatically($parentModel, $relationship);
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

        if (! str_ends_with($namespace, '\\RelationManagers')) {
            return null;
        }

        return substr($namespace, 0, -strlen('\\RelationManagers'));
    }
}
