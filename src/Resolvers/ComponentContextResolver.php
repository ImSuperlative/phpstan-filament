<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Resolvers;

use ImSuperlative\PhpstanFilament\Data\FilamentContext;
use ImSuperlative\PhpstanFilament\Data\FilamentPageAnnotation;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use ImSuperlative\PhpstanFilament\Support\ModelReflectionHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;

final class ComponentContextResolver
{
    public function __construct(
        protected readonly FilamentClassHelper $filamentClassHelper,
        protected readonly ResourceModelResolver $resourceModelResolver,
        protected readonly AnnotationReader $annotationReader,
        protected readonly ReflectionProvider $reflectionProvider,
        protected readonly ModelReflectionHelper $modelReflectionHelper,
        protected readonly VirtualAnnotationProvider $virtualAnnotationProvider,
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
        $models = $this->resolveModelClassesFromScope($scope);

        return count($models) === 1 ? $models[0] : null;
    }

    /**
     * Resolve all possible model classes for a scope.
     *
     * Returns a single-element array when the model is unambiguous
     * (annotation, class structure, namespace inference, or all virtual
     * callers agree). Returns multiple elements when virtual callers
     * map to different models (e.g. a shared helper used by multiple resources).
     *
     * @return list<string>
     */
    public function resolveModelClassesFromScope(Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        // Priority 1: PHPDoc annotations (@filament-model, @filament-page Page<Model>)
        $annotationModel = $this->resolveFromAnnotations($classReflection);
        if ($annotationModel !== null) {
            return [$annotationModel];
        }

        // Priority 2: Resource page / relation manager class structure
        $classModel = $this->resolveFromClassStructure($classReflection->getName(), $scope);
        if ($classModel !== null) {
            return [$classModel];
        }

        // Priority 3: Virtual annotations (auto-inferred from pre-scan)
        $virtualModels = $this->resolveAllFromVirtualAnnotations($classReflection->getName());
        if ($virtualModels !== []) {
            return $virtualModels;
        }

        // Priority 4: Namespace convention inference
        $inferred = $this->inferModelFromNamespace($classReflection->getName());

        return $inferred !== null ? [$inferred] : [];
    }

    /**
     * Resolve model from resource page or relation manager class structure.
     */
    protected function resolveFromClassStructure(string $className, Scope $scope): ?string
    {
        $context = $this->fromClassName($className);

        if (! $context->hasModelContext()) {
            return null;
        }

        if ($context->isNested && $context->modelClass !== null) {
            return $this->resolveNestedModel($className, $context->modelClass, $scope)
                ?? $context->modelClass;
        }

        return $context->modelClass;
    }

    /**
     * Resolve model from annotations (attributes + PHPDoc) on the class.
     */
    protected function resolveFromAnnotations(ClassReflection $classReflection): ?string
    {
        // @filament-model / #[FilamentModel] takes priority
        $modelAnnotation = $this->annotationReader->readModelAnnotation($classReflection);
        if ($modelAnnotation !== null) {
            return $this->resolveTypeName($modelAnnotation->typeAsString(), $classReflection);
        }

        // @filament-page / #[FilamentPage] Page<Model> — extract model from generic
        $pageAnnotations = $this->annotationReader->readPageAnnotations($classReflection);

        return $this->resolveModelFromPageAnnotations($pageAnnotations, $classReflection);
    }

    /**
     * Resolve all unique models from virtual annotations.
     *
     * @return list<string>
     */
    protected function resolveAllFromVirtualAnnotations(string $className): array
    {
        $annotations = $this->virtualAnnotationProvider->getPageAnnotations($className);

        if ($annotations === []) {
            return [];
        }

        $models = [];

        foreach ($annotations as $annotation) {
            $model = $this->resolveModelFromVirtualAnnotation($annotation);

            if ($model !== null) {
                $models[] = $model;
            }
        }

        return array_values(array_unique($models));
    }

    protected function resolveModelFromVirtualAnnotation(FilamentPageAnnotation $annotation): ?string
    {
        $modelType = $annotation->modelType();

        if ($modelType !== null) {
            return (string) $modelType;
        }

        // No generic — resolve caller → resource → model
        foreach ($annotation->pageTypes() as $pageType) {
            $callerClass = (string) $pageType;

            $model = $this->resourceModelResolver->resolve($callerClass);

            if ($model !== null) {
                return $model;
            }
        }

        return null;
    }

    /**
     * @param  array<FilamentPageAnnotation>  $annotations
     */
    protected function resolveModelFromPageAnnotations(array $annotations, ClassReflection $classReflection): ?string
    {
        foreach ($annotations as $annotation) {
            $modelType = $annotation->modelType();
            if ($modelType !== null) {
                return $this->resolveTypeName((string) $modelType, $classReflection);
            }

            // No generic — resolve page → resource → model
            foreach ($annotation->pageTypes() as $pageType) {
                $pageName = $this->resolveTypeName((string) $pageType, $classReflection);
                $model = $this->resourceModelResolver->resolve($pageName);
                if ($model !== null) {
                    return $model;
                }
            }
        }

        return null;
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
     */
    protected function inferModelFromNamespace(string $className): ?string
    {
        $resourceClass = $this->filamentClassHelper->inferResourceFromNamespace($className);

        return $resourceClass !== null
            ? $this->resourceModelResolver->resolve($resourceClass)
            : null;
    }

    /**
     * Resolve a type name using the class's nameScope (supports short names via use statements).
     */
    protected function resolveTypeName(string $typeName, ClassReflection $classReflection): string
    {
        if ($this->reflectionProvider->hasClass($typeName)) {
            return $typeName;
        }

        $phpDoc = $classReflection->getResolvedPhpDoc();
        $nameScope = $phpDoc?->getNullableNameScope();

        return $nameScope !== null
            ? $nameScope->resolveStringName($typeName)
            : $typeName;
    }
}
