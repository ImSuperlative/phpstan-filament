<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner\Transformers\Graph;

use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceModels;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourcePages;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceRelations;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\Graph\Model\ParsesModelFromClass;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\GraphTransformer;
use ImSuperlative\PhpstanFilament\Support\FilamentComponent as FC;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ReflectionProvider;

final class ModelTransformer implements GraphTransformer
{
    use ParsesModelFromClass;

    public function __construct(
        protected ReflectionProvider $reflectionProvider,
        protected FileParser $fileParser,
    ) {}

    public function transform(ProjectScanResult $result): ProjectScanResult
    {
        $resourceModels = $this->buildResourceModelMap($result);

        $models = [
            ...$resourceModels,
            ...$this->buildPageModelMap($result, $resourceModels),
            ...$this->buildRelationManagerModelMap($result, $resourceModels),
        ];

        return $result->set(new ResourceModels($models));
    }

    /** @return array<class-string, list<class-string>> */
    public function componentMappings(ProjectScanResult $result): array
    {
        return [];
    }

    /** @return array<string, string> */
    protected function buildResourceModelMap(ProjectScanResult $result): array
    {
        $models = [];

        foreach ($result->roots as $filePath) {
            if (! isset($result->index[$filePath])) {
                continue;
            }

            $record = $result->index[$filePath];
            $fqcn = $record->fullyQualifiedName;

            if (! $this->reflectionProvider->hasClass($fqcn)) {
                continue;
            }

            $model = $this->extractModelFromReturnType($fqcn)
                ?? $this->extractModelFromAst($fqcn, $filePath, $record)
                ?? $this->extractModelFromProperty($fqcn);

            if ($model !== null) {
                $models[$fqcn] = $model;
            }
        }

        return $models;
    }

    /** @param array<string, string> $resourceModels */
    protected function buildPageModelMap(ProjectScanResult $result, array $resourceModels): array
    {
        $pages = $result->find(ResourcePages::class);
        if ($pages === null) {
            return [];
        }

        $models = [];

        foreach ($pages->all() as $resource => $pageMap) {
            $parentModel = $resourceModels[$resource] ?? null;
            if ($parentModel === null) {
                continue;
            }

            foreach ($pageMap as $pageFqcn) {
                $models[$pageFqcn] = $this->usesRelationshipTable($pageFqcn)
                    ? ($this->extractRelatedModelFromRelationship($pageFqcn, $parentModel)
                        ?? $this->resolveFromRelatedResource($pageFqcn, $resourceModels)
                        ?? $parentModel)
                    : $parentModel;
            }
        }

        return $models;
    }

    protected function usesRelationshipTable(string $className): bool
    {
        return $this->reflectionProvider->hasClass($className)
            && isset($this->reflectionProvider->getClass($className)
                ->getTraits(true)[FC::INTERACTS_WITH_RELATIONSHIP_TABLE]);
    }

    /** @param array<string, string> $resourceModels */
    protected function buildRelationManagerModelMap(ProjectScanResult $result, array $resourceModels): array
    {
        $relations = $result->find(ResourceRelations::class);
        if ($relations === null) {
            return [];
        }

        $models = [];

        foreach ($relations->all() as $resource => $managerFqcns) {
            $parentModel = $resourceModels[$resource] ?? null;
            if ($parentModel === null) {
                continue;
            }

            foreach ($managerFqcns as $managerFqcn) {
                $models[$managerFqcn] = $this->extractRelatedModelFromRelationship($managerFqcn, $parentModel)
                    ?? $this->resolveFromRelatedResource($managerFqcn, $resourceModels)
                    ?? $parentModel;
            }
        }

        return $models;
    }

    protected function extractRelatedModelFromRelationship(string $managerFqcn, string $parentModel): ?string
    {
        $relationship = $this->readStaticStringProperty($managerFqcn, 'relationship');
        if ($relationship === null) {
            return null;
        }

        return $this->extractModelFromRelationshipMethod($parentModel, $relationship);
    }

    /** @param array<string, string> $resourceModels */
    protected function resolveFromRelatedResource(string $className, array $resourceModels): ?string
    {
        $relatedResource = $this->readStaticStringProperty($className, 'relatedResource');

        return $relatedResource !== null
            ? ($resourceModels[$relatedResource] ?? null)
            : null;
    }

    protected function extractModelFromRelationshipMethod(string $modelClass, string $relationship): ?string
    {
        if (! $this->reflectionProvider->hasClass($modelClass)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($modelClass);

        foreach ([$relationship, $relationship.'s'] as $candidate) {
            if (! $classReflection->hasMethod($candidate)) {
                continue;
            }

            $returnType = $classReflection->getMethod($candidate, new OutOfClassScope)
                ->getVariants()[0]
                ->getReturnType();

            if (! $returnType->isObject()->yes()) {
                continue;
            }

            $model = array_find(
                $returnType->getReferencedClasses(),
                fn (string $class) => $this->isConcreteModelClass($class) && $this->isEloquentModel($class),
            );

            if ($model !== null) {
                return $model;
            }
        }

        return null;
    }

    protected function isEloquentModel(string $className): bool
    {
        return $this->reflectionProvider->hasClass($className)
            && $this->reflectionProvider->getClass($className)
                ->isSubclassOfClass($this->reflectionProvider->getClass('Illuminate\Database\Eloquent\Model'));
    }
}
