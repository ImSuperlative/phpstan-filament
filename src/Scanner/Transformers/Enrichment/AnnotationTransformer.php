<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner\Transformers\Enrichment;

use ImSuperlative\PhpstanFilament\Data\FilamentTagAnnotation;
use ImSuperlative\PhpstanFilament\Data\FileMetadata;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentAnnotations;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Data\Scanner\ExplicitAnnotations;
use ImSuperlative\PhpstanFilament\Resolvers\AnnotationParser;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\EnrichmentTransformer;
use ImSuperlative\PhpstanFilament\Support\NamespaceHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;

final class AnnotationTransformer implements EnrichmentTransformer
{
    public function __construct(
        protected ReflectionProvider $reflectionProvider,
        protected AnnotationParser $annotationReader,
    ) {}

    public function transform(ProjectScanResult $result): ProjectScanResult
    {
        $componentToResources = $result->find(ComponentToResources::class);

        if ($componentToResources === null) {
            return $result->set(new ComponentAnnotations([]));
        }

        $metadataByClass = $this->buildClassMetadataMap($result);
        $annotations = [];

        foreach ($componentToResources->all() as $componentClass => $resourceClasses) {
            $explicit = $this->readAnnotations($componentClass, $metadataByClass[$componentClass] ?? null);

            if ($explicit !== null) {
                $annotations[$componentClass] = $explicit;
            }
        }

        return $result->set(new ComponentAnnotations($annotations));
    }

    /** @return array<string, FileMetadata> FQCN => FileMetadata */
    protected function buildClassMetadataMap(ProjectScanResult $result): array
    {
        $map = [];
        foreach ($result->index as $metadata) {
            $map[$metadata->fullyQualifiedName] = $metadata;
        }

        return $map;
    }

    protected function readAnnotations(string $componentClass, ?FileMetadata $metadata): ?ExplicitAnnotations
    {
        if (! $this->reflectionProvider->hasClass($componentClass)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($componentClass);

        $model = $this->extractModelFqcn($classReflection, $metadata);
        $pageModels = $this->extractPageModelMap($classReflection, $metadata);
        $states = $this->extractFieldNames($this->annotationReader->readStateAnnotations($classReflection));
        $fields = $this->extractFieldNames($this->annotationReader->readFieldAnnotations($classReflection));

        if ($model === null && $pageModels === [] && $states === [] && $fields === []) {
            return null;
        }

        return new ExplicitAnnotations(
            model: $model,
            pageModels: $pageModels,
            states: $states,
            fields: $fields,
        );
    }

    protected function extractModelFqcn(ClassReflection $classReflection, ?FileMetadata $metadata): ?string
    {
        $annotation = $this->annotationReader->readModelAnnotation($classReflection);

        return $annotation !== null
            ? $this->toFullyQualifiedClassName($annotation->typeAsString(), $metadata)
            : null;
    }

    /** @return array<string, ?string> page FQCN => model FQCN|null */
    protected function extractPageModelMap(ClassReflection $classReflection, ?FileMetadata $metadata): array
    {
        $annotations = $this->annotationReader->readPageAnnotations($classReflection);
        if ($annotations === []) {
            return [];
        }

        $map = [];
        foreach ($annotations as $annotation) {
            $modelType = $annotation->modelType();
            $model = $modelType !== null
                ? $this->toFullyQualifiedClassName((string) $modelType, $metadata)
                : null;

            foreach ($annotation->pageTypes() as $pageType) {
                $map[$this->toFullyQualifiedClassName((string) $pageType, $metadata)] = $model;
            }
        }

        return $map;
    }

    /**
     * @param  array<FilamentTagAnnotation>  $annotations
     * @return list<string>
     */
    protected function extractFieldNames(array $annotations): array
    {
        return array_map(fn (FilamentTagAnnotation $a) => $a->typeAsString(), $annotations);
    }

    protected function toFullyQualifiedClassName(string $name, ?FileMetadata $metadata): string
    {
        if ($metadata === null) {
            return $name;
        }

        // If the name already resolves as a class, use it directly (avoids double-qualifying)
        if ($this->reflectionProvider->hasClass($name)) {
            return $name;
        }

        return NamespaceHelper::toFullyQualified($name, $metadata->useMap, $metadata->namespace);
    }
}
