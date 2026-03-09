<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Resolvers;

use ImSuperlative\FilamentPhpstan\Data\FieldPathResult;
use ImSuperlative\FilamentPhpstan\Data\ResolvedSegment;
use ImSuperlative\FilamentPhpstan\Data\SegmentTag;
use ImSuperlative\FilamentPhpstan\Support\ModelReflectionHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;

class FieldPathResolver
{
    public function __construct(
        protected readonly ModelReflectionHelper $modelReflectionHelper,
        protected readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function resolve(string $fieldName, string $modelClass, Scope $scope): FieldPathResult
    {
        $segments = explode('.', $fieldName);
        $resolved = [];
        $currentClass = $modelClass;

        foreach ($segments as $i => $segmentName) {
            $isLast = $i === count($segments) - 1;

            $segment = $isLast
                ? $this->resolveLeaf($segmentName, $currentClass, $scope)
                : $this->resolveIntermediate($segmentName, $currentClass, $scope);

            $resolved[] = $segment;

            // Stop walking if unresolvable (no tags) or no class to walk into
            if ($segment->tags === [] || (! $isLast && $segment->resolvedClass === null)) {
                $remaining = array_slice($segments, $i + 1);

                return new FieldPathResult($modelClass, $resolved, $remaining);
            }

            if (! $isLast && $segment->resolvedClass !== null) {
                $currentClass = $segment->resolvedClass;
            }
        }

        return new FieldPathResult($modelClass, $resolved, []);
    }

    protected function resolveIntermediate(string $name, string $currentClass, Scope $scope): ResolvedSegment
    {
        if (! $this->reflectionProvider->hasClass($currentClass)) {
            return new ResolvedSegment($name, [], null, null);
        }

        $tags = [];
        $resolvedClass = null;

        // Check relation (highest priority for resolvedClass)
        $relatedModel = $this->modelReflectionHelper->resolveRelatedModel($currentClass, $name, $scope);
        if ($relatedModel !== null) {
            $tags[] = SegmentTag::Relation;
            $resolvedClass = $relatedModel;
        } elseif ($this->modelReflectionHelper->isRelationship($currentClass, $name, $scope) === true) {
            // Relation exists but can't resolve model (e.g. morphTo) — tag but stop walking
            $tags[] = SegmentTag::Relation;
        }

        // Check property
        if ($this->modelReflectionHelper->hasProperty($currentClass, $name)) {
            $tags[] = SegmentTag::Property;
        }

        // Check method
        if ($this->modelReflectionHelper->hasMethod($currentClass, $name)) {
            $tags[] = SegmentTag::Method;
        }

        // Check collection item type (before typed property — collection item is more specific)
        $collectionType = $this->modelReflectionHelper->resolveCollectionItemType($currentClass, $name, $scope);
        if ($collectionType !== null) {
            $tags[] = SegmentTag::CollectionItem;
            $resolvedClass ??= $collectionType;
        }

        // Check typed property (resolves to object class)
        $objectType = $this->modelReflectionHelper->resolvePropertyObjectType($currentClass, $name, $scope);
        if ($objectType !== null) {
            $tags[] = SegmentTag::TypedProperty;
            $resolvedClass ??= $objectType;
        }

        return new ResolvedSegment($name, $tags, $resolvedClass, null);
    }

    protected function resolveLeaf(string $name, string $currentClass, Scope $scope): ResolvedSegment
    {
        if (! $this->reflectionProvider->hasClass($currentClass)) {
            return new ResolvedSegment($name, [], null, null);
        }

        $tags = [];
        $type = null;

        // Check property + get type
        if ($this->modelReflectionHelper->hasProperty($currentClass, $name)) {
            $tags[] = SegmentTag::Property;
            $classReflection = $this->reflectionProvider->getClass($currentClass);
            $type = $classReflection->hasInstanceProperty($name)
                ? $classReflection->getInstanceProperty($name, $scope)->getReadableType()
                : null;
        }

        // Check relation
        $isRelation = $this->modelReflectionHelper->isRelationship($currentClass, $name, $scope);
        if ($isRelation === true) {
            $tags[] = SegmentTag::Relation;
        }

        // Check method
        if ($this->modelReflectionHelper->hasMethod($currentClass, $name)) {
            $tags[] = SegmentTag::Method;
        }

        return new ResolvedSegment($name, $tags, null, $type);
    }
}
