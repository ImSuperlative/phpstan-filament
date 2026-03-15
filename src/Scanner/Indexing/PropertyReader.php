<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner\Indexing;

use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentDeclaration;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentDeclarations;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Resolvers\AnnotationReader;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Support\FilamentComponent as FC;
use PHPStan\Reflection\ReflectionProvider;
use ReflectionException;

class PropertyReader
{
    /** @var list<string> */
    protected const array FILAMENT_BASE_CLASSES = [
        FC::RESOURCE,
        FC::RELATION_MANAGER,
        FC::RESOURCE_PAGE,
        FC::PAGE,
    ];

    /** @var array<string, string> property name => getter method */
    protected const array PROPERTY_MAP = [
        'model' => 'getModel',
        'resource' => 'getResource',
        'relatedResource' => 'getRelatedResource',
        'relationship' => 'getRelationship',
        'cluster' => 'getCluster',
        'parentResource' => 'getParentResource',
    ];

    public function __construct(
        protected ReflectionProvider $reflectionProvider,
        protected AnnotationReader $annotationReader,
    ) {}

    public function read(ProjectScanResult $result): ProjectScanResult
    {
        $componentToResources = $result->find(ComponentToResources::class);
        if ($componentToResources === null) {
            return $result->set(new ComponentDeclarations([]));
        }

        $declarations = [];

        foreach ($componentToResources->all() as $fqcn => $resourceClasses) {
            $declaration = $this->readDeclaration($fqcn);
            if ($declaration !== null) {
                $declarations[$fqcn] = $declaration;
            }
        }

        return $result->set(new ComponentDeclarations($declarations));
    }

    protected function readDeclaration(string $className): ?ComponentDeclaration
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        // Model has annotation priority — @filament-model or @filament-page<Model> override everything
        $model = $this->resolveModelFromAnnotations($className)
            ?? $this->resolveValue($className, 'model', 'getModel');

        /** @var array{resource: ?string, relatedResource: ?string, relationship: ?string, cluster: ?string, parentResource: ?string} $values */
        $values = [];
        foreach (array_diff_key(self::PROPERTY_MAP, ['model' => true]) as $property => $getter) {
            $values[$property] = $this->resolveValue($className, $property, $getter);
        }

        if ($model === null && array_filter($values) === []) {
            return null;
        }

        return new ComponentDeclaration(
            model: $model,
            resourceClass: $values['resource'],
            relatedResourceClass: $values['relatedResource'],
            relationshipName: $values['relationship'],
            cluster: $values['cluster'],
            parentResource: $values['parentResource'],
        );
    }

    /**
     * Check @filament-model and @filament-page<Model> annotations.
     * If set, this is the definitive model — skip property/getter resolution.
     */
    protected function resolveModelFromAnnotations(string $className): ?string
    {
        $classReflection = $this->reflectionProvider->getClass($className);

        $modelAnnotation = $this->annotationReader->readModelAnnotation($classReflection);
        if ($modelAnnotation !== null) {
            return $modelAnnotation->typeAsString();
        }

        $pageAnnotations = $this->annotationReader->readPageAnnotations($classReflection);
        foreach ($pageAnnotations as $annotation) {
            $modelType = $annotation->modelType();
            if ($modelType !== null) {
                return (string) $modelType;
            }
        }

        return null;
    }

    /**
     * Walk the class hierarchy reading property/getter per class.
     * Stops at Filament base classes to avoid generic defaults.
     */
    protected function resolveValue(string $className, string $property, string $getter): ?string
    {
        $current = $className;

        while ($current !== null) {
            if (in_array($current, self::FILAMENT_BASE_CLASSES, true)) {
                return null;
            }

            if (! $this->reflectionProvider->hasClass($current)) {
                return null;
            }

            // 1. Own static property (declared on this class, not inherited)
            $value = $this->readOwnStaticProperty($current, $property);
            if ($value !== null) {
                return $value;
            }

            // 2. Own getter method return value
            $value = $this->readOwnGetterReturnType($current, $getter);
            if ($value !== null) {
                return $value;
            }

            // Walk to parent
            $reflection = $this->reflectionProvider->getClass($current);
            $parent = $reflection->getParentClass();
            $current = $parent?->getName();
        }

        return null;
    }

    /**
     * Read a static string property only if declared on this exact class.
     */
    protected function readOwnStaticProperty(string $className, string $property): ?string
    {
        try {
            $nativeClass = $this->reflectionProvider->getClass($className)->getNativeReflection();
            $prop = $nativeClass->getProperty($property);

            if ($prop->getDeclaringClass()->getName() !== $className) {
                return null;
            }

            $value = $prop->getDefaultValue();

            return is_string($value) && $value !== '' ? $value : null;
        } catch (ReflectionException) {
            return null;
        }
    }

    /**
     * Read return type from a getter method only if declared on this exact class.
     * Uses PHPStan reflection to check return type for a class-string.
     */
    protected function readOwnGetterReturnType(string $className, string $getter): ?string
    {
        $classReflection = $this->reflectionProvider->getClass($className);

        if (! $classReflection->hasMethod($getter)) {
            return null;
        }

        $method = $classReflection->getNativeMethod($getter);

        if ($method->getDeclaringClass()->getName() !== $className) {
            return null;
        }

        $returnType = $method->getVariants()[0]->getReturnType();

        if (! $returnType->isObject()->yes()) {
            return null;
        }

        $classes = $returnType->getReferencedClasses();

        return count($classes) === 1 ? $classes[0] : null;
    }
}
