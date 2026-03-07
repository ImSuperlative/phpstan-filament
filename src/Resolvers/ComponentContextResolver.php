<?php

namespace ImSuperlative\FilamentPhpstan\Resolvers;

use ImSuperlative\FilamentPhpstan\Collectors\CustomComponentRegistry;
use ImSuperlative\FilamentPhpstan\Collectors\SchemaCallSiteRegistry;
use ImSuperlative\FilamentPhpstan\Collectors\TableQueryRegistry;
use ImSuperlative\FilamentPhpstan\Data\FilamentContext;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use ImSuperlative\FilamentPhpstan\Support\ModelReflectionHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;

final class ComponentContextResolver
{
    public function __construct(
        protected readonly FilamentClassHelper $filamentClassHelper,
        protected readonly ResourceModelResolver $resourceModelResolver,
        protected readonly AnnotationReader $annotationReader,
        protected readonly TableQueryRegistry $tableQueryRegistry,
        protected readonly ReflectionProvider $reflectionProvider,
        protected readonly ModelReflectionHelper $modelReflectionHelper,
        protected CustomComponentRegistry $customComponentRegistry,
        protected readonly SchemaCallSiteRegistry $schemaCallSiteRegistry,
    ) {}

    /**
     * Resolve context from a class name (e.g. from Scope::getClassReflection()).
     */
    public function fromClassName(string $className): FilamentContext
    {
        return match (true) {
            $this->filamentClassHelper->isResourceClass($className) => new FilamentContext(
                resourceClass: $className,
                modelClass: $this->resourceModelResolver->resolve($className),
            ),
            $this->filamentClassHelper->isResourceScoped($className) => new FilamentContext(
                resourceClass: $this->filamentClassHelper->readStaticProperty($className, 'resource'),
                modelClass: $this->resourceModelResolver->resolve($className),
                isNested: $this->filamentClassHelper->isNestedResource($className),
            ),
            default => new FilamentContext,
        };
    }

    /**
     * Create context from a @filament-model annotation value.
     */
    public function fromAnnotation(string $modelClass): FilamentContext
    {
        return new FilamentContext(
            modelClass: $modelClass,
        );
    }

    /**
     * Create context with just a component class.
     */
    public function forComponent(string $componentClass, ?FilamentContext $parent = null): FilamentContext
    {
        return new FilamentContext(
            componentClass: $componentClass,
            resourceClass: $parent?->resourceClass,
            modelClass: $parent?->modelClass,
            isNested: $parent !== null && $parent->isNested,
        );
    }

    public function resolveModelClassFromScope(Scope $scope): ?string
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $annotationModel = $this->resolveFromAnnotation($classReflection);
        if ($annotationModel !== null) {
            return $annotationModel;
        }

        $methodName = $scope->getFunctionName();
        if ($methodName !== null) {
            $queryModel = $this->tableQueryRegistry->lookup($classReflection->getName(), $methodName);
            if ($queryModel !== null) {
                return $queryModel;
            }
        }

        $context = $this->fromClassName($classReflection->getName());
        if (! $context->hasModelContext()) {
            // 1. Direct model mapping registered by SchemaCallSiteCollector during analysis
            return $this->schemaCallSiteRegistry->getModelForClass($classReflection->getName())
                // 2. Convention-based: walk parent namespaces to find a Resource class and resolve its model
                ?? $this->inferModelFromNamespace($classReflection->getName())
                // 3. Caller graph: look up classes that instantiate this one (via pre-scanner), then resolve their model context
                ?? $this->resolveModelFromCallers($classReflection->getName())
                // 4. Collector-populated: model context captured from third-party/custom components that call ::make()
                ?? $this->customComponentRegistry->getModelForClass($classReflection->getName());
        }

        if ($context->isNested && $context->modelClass !== null) {
            return $this->resolveNestedModel($classReflection->getName(), $context->modelClass, $scope)
                ?? $context->modelClass;
        }

        return $context->modelClass;
    }

    protected function resolveNestedModel(string $className, string $parentModelClass, Scope $scope): ?string
    {
        $relationship = $this->filamentClassHelper->readStaticProperty($className, 'relationship');

        return $relationship === null
            ? null
            : $this->modelReflectionHelper->resolveRelatedModel($parentModelClass, $relationship, $scope);
    }

    /**
     * Infer the model by detecting a resource class in the parent namespace.
     *
     * Filament convention: schema/table helper classes live under Schemas/, Tables/, or Pages/
     * sub-namespaces of the resource namespace. e.g.
     *   App\...\FormVersions\Schemas\FormVersionInfolist → App\...\FormVersions\FormVersionResource
     */
    protected function inferModelFromNamespace(string $className): ?string
    {
        $resourceClass = $this->filamentClassHelper->inferResourceFromNamespace($className);

        return $resourceClass !== null
            ? $this->resourceModelResolver->resolve($resourceClass)
            : null;
    }

    /**
     * Resolve model by looking up callers registered by the pre-scanner
     * and resolving the model from the caller's context.
     */
    /**
     * Resolve model by looking up callers registered by the pre-scanner
     * and resolving the model from the caller's context.
     */
    protected function resolveModelFromCallers(string $className): ?string
    {
        foreach ($this->schemaCallSiteRegistry->getCallersForClass($className) as $caller) {
            $context = $this->fromClassName($caller);

            if ($context->hasModelContext()) {
                return $context->modelClass;
            }

            $model = $this->schemaCallSiteRegistry->getModelForClass($caller)
                ?? $this->inferModelFromNamespace($caller);

            if ($model !== null) {
                return $model;
            }
        }

        return null;
    }

    protected function resolveFromAnnotation(ClassReflection $classReflection): ?string
    {
        $phpDoc = $classReflection->getResolvedPhpDoc();
        if ($phpDoc === null) {
            return null;
        }

        $annotation = $this->annotationReader->readModelAnnotation($phpDoc->getPhpDocString());
        if ($annotation === null) {
            return null;
        }

        // If the annotation is already a FQN (exists as a class), use it directly.
        // Otherwise, resolve through the namespace scope (supports short names via use statements).
        $type = $annotation->typeAsString();

        if ($this->reflectionProvider->hasClass($type)) {
            return $type;
        }

        $nameScope = $phpDoc->getNullableNameScope();

        return $nameScope !== null
            ? $nameScope->resolveStringName($type)
            : $type;
    }
}
