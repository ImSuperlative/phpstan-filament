<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\Handlers;

use ImSuperlative\FilamentPhpstan\Data\FilamentPageAnnotation;
use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureParameterHandler;
use ImSuperlative\FilamentPhpstan\Resolvers\AnnotationReader;
use ImSuperlative\FilamentPhpstan\Resolvers\VirtualAnnotationProvider;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class LivewireClosureHandler implements ClosureParameterHandler
{
    public function __construct(
        protected readonly FilamentClassHelper $filamentClassHelper,
        protected readonly AnnotationReader $annotationReader,
        protected readonly VirtualAnnotationProvider $virtualAnnotationProvider,
    ) {}

    public function resolveType(string $paramName, bool $hasTypeHint, ClosureHandlerContext $context, ?Type $mapType): ?Type
    {
        if ($hasTypeHint || $paramName !== 'livewire' || $mapType === null) {
            return null;
        }

        $classReflection = $context->scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $schemaClass = $classReflection->getName();

        // If the scope class is already a resource page or relation manager, use it directly
        if ($this->filamentClassHelper->isResourceScoped($schemaClass)) {
            return new ObjectType($schemaClass);
        }

        // Priority 1: @filament-page annotation — explicit override
        $annotationClasses = $this->resolveFromAnnotation($classReflection);
        if ($annotationClasses !== []) {
            return $this->buildUnionType($annotationClasses);
        }

        // Priority 2: Virtual annotations (auto-inferred from pre-scan)
        $virtualClasses = $this->resolveFromVirtualAnnotations($schemaClass);
        if ($virtualClasses !== []) {
            return $this->buildUnionType($virtualClasses);
        }

        // Priority 3: Namespace convention inference
        $inferredClasses = $this->resolveFromNamespaceInference($schemaClass);
        if ($inferredClasses !== []) {
            return $this->buildUnionType($inferredClasses);
        }

        return null;
    }

    /**
     * @return list<string> Fully-qualified class names from @filament-page / #[FilamentPage]
     */
    protected function resolveFromAnnotation(ClassReflection $classReflection): array
    {
        $annotations = $this->annotationReader->readPageAnnotations($classReflection);
        if ($annotations === []) {
            return [];
        }

        $phpDoc = $classReflection->getResolvedPhpDoc();
        $nameScope = $phpDoc?->getNullableNameScope();

        return $this->extractPageClassNames($annotations, $nameScope);
    }

    /**
     * @return list<string> Fully-qualified class names from virtual annotations
     */
    protected function resolveFromVirtualAnnotations(string $schemaClass): array
    {
        $annotations = $this->virtualAnnotationProvider->getPageAnnotations($schemaClass);
        if ($annotations === []) {
            return [];
        }

        return $this->expandAnnotationsToPages($annotations);
    }

    /**
     * Extract page class names from annotations, resolving via nameScope.
     *
     * @param  array<FilamentPageAnnotation>  $annotations
     * @return list<string>
     */
    protected function extractPageClassNames(array $annotations, ?\PHPStan\Analyser\NameScope $nameScope): array
    {
        $classNames = [];

        foreach ($annotations as $annotation) {
            foreach ($annotation->pageTypes() as $type) {
                $name = (string) $type;
                $classNames[] = $nameScope !== null
                    ? $nameScope->resolveStringName($name)
                    : $name;
            }
        }

        return $classNames;
    }

    /**
     * Expand virtual annotations to page class names.
     * Virtual annotations have FQN types — no nameScope needed.
     * Resource callers are expanded to their pages.
     *
     * @param  array<FilamentPageAnnotation>  $annotations
     * @return list<string>
     */
    protected function expandAnnotationsToPages(array $annotations): array
    {
        $classNames = [];

        foreach ($annotations as $annotation) {
            foreach ($annotation->pageTypes() as $type) {
                $caller = (string) $type;

                // Expand Resource classes to their pages (Resource isn't a Livewire component)
                if ($this->filamentClassHelper->isResourceClass($caller)) {
                    foreach ($this->filamentClassHelper->resolveResourcePages($caller) as $page) {
                        if (! in_array($page, $classNames, true)) {
                            $classNames[] = $page;
                        }
                    }

                    continue;
                }

                if (! in_array($caller, $classNames, true)) {
                    $classNames[] = $caller;
                }
            }
        }

        return $classNames;
    }

    /**
     * @return list<string>
     */
    protected function resolveFromNamespaceInference(string $schemaClass): array
    {
        $resourceClass = $this->filamentClassHelper->inferResourceFromNamespace($schemaClass);

        return $resourceClass !== null
            ? $this->filamentClassHelper->resolveResourcePages($resourceClass)
            : [];
    }

    /** @param list<string> $classNames */
    protected function buildUnionType(array $classNames): Type
    {
        $types = array_map(fn (string $class) => new ObjectType($class), $classNames);

        return count($types) === 1 ? $types[0] : TypeCombinator::union(...$types);
    }
}
