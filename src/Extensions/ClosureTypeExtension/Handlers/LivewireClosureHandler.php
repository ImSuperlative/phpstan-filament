<?php

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\Handlers;

use ImSuperlative\FilamentPhpstan\Collectors\SchemaCallSiteRegistry;
use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureParameterHandler;
use ImSuperlative\FilamentPhpstan\Resolvers\AnnotationReader;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class LivewireClosureHandler implements ClosureParameterHandler
{
    public function __construct(
        protected readonly FilamentClassHelper $filamentClassHelper,
        protected readonly SchemaCallSiteRegistry $schemaCallSiteRegistry,
        protected readonly AnnotationReader $annotationReader,
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

        // Priority 2: Caller registry (populated by pre-scanner and collector)
        $classNames = [];

        foreach ($this->schemaCallSiteRegistry->getCallersForClass($schemaClass) as $caller) {
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

        // Priority 3: Fallback — infer resource from schema namespace convention
        if ($classNames === []) {
            $resourceClass = $this->filamentClassHelper->inferResourceFromNamespace($schemaClass);
            if ($resourceClass !== null) {
                $classNames = $this->filamentClassHelper->resolveResourcePages($resourceClass);
            }
        }

        if ($classNames === []) {
            return null;
        }

        return $this->buildUnionType($classNames);
    }

    /**
     * @return list<string> Fully-qualified class names from @/filament-page
     */
    protected function resolveFromAnnotation(ClassReflection $classReflection): array
    {
        $phpDoc = $classReflection->getResolvedPhpDoc();
        if ($phpDoc === null) {
            return [];
        }

        $annotations = $this->annotationReader->readPageAnnotations($phpDoc->getPhpDocString());
        if ($annotations === []) {
            return [];
        }

        $nameScope = $phpDoc->getNullableNameScope();
        $classNames = [];

        foreach ($annotations as $annotation) {
            foreach ($annotation->types() as $type) {
                $name = (string) $type;
                $classNames[] = $nameScope !== null
                    ? $nameScope->resolveStringName($name)
                    : $name;
            }
        }

        return $classNames;
    }

    /** @param list<string> $classNames */
    protected function buildUnionType(array $classNames): Type
    {
        $types = array_map(fn (string $class) => new ObjectType($class), $classNames);

        return count($types) === 1 ? $types[0] : TypeCombinator::union(...$types);
    }
}
