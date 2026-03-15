<?php

/** @noinspection ClassConstantCanBeUsedInspection */

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner\Transformers\Enrichment;

use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentAnnotations;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentContext;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentDeclaration;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentDeclarations;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentNode;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentTag;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Data\Scanner\ExplicitAnnotations;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceModels;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourcePages;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\EnrichmentTransformer;
use ImSuperlative\PhpstanFilament\Support\FilamentComponent as FC;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;

final class ComponentContextTransformer implements EnrichmentTransformer
{
    public function __construct(
        protected ReflectionProvider $reflectionProvider,
    ) {}

    public function transform(ProjectScanResult $result): ProjectScanResult
    {
        $componentToResources = $result->find(ComponentToResources::class);

        if ($componentToResources === null) {
            return $result->set(new ComponentContext([]));
        }

        $resourceModels = $result->find(ResourceModels::class);
        $resourcePages = $result->find(ResourcePages::class);
        $componentAnnotations = $result->find(ComponentAnnotations::class);
        $componentDeclarations = $result->find(ComponentDeclarations::class);

        $knownComponents = $componentToResources->all();

        // Propagate to subclasses: any indexed class extending a known component inherits its resources
        foreach ($result->index as $record) {
            if ($record->extends !== null
                && isset($knownComponents[$record->extends])
                && ! isset($knownComponents[$record->fullyQualifiedName])
            ) {
                $knownComponents[$record->fullyQualifiedName] = $knownComponents[$record->extends];
            }
        }

        $context = [];

        foreach ($knownComponents as $componentClass => $resourceClasses) {
            $explicit = $componentAnnotations?->get($componentClass);
            $declaration = $componentDeclarations?->get($componentClass);

            $context[$componentClass] = new ComponentNode(
                tags: $this->computeTags($componentClass, $declaration, $resourceClasses),
                explicitModel: $explicit?->model,
                pageModels: $this->resolvePageModels($explicit, $resourceClasses, $resourceModels, $resourcePages),
                resourceModels: $this->reduceResourceModels($resourceClasses, $resourceModels),
                resourcePages: $this->reduceResourcePages($resourceClasses, $resourcePages),
                owningResources: $resourceClasses,
                declaration: $declaration ?? new ComponentDeclaration,
            );
        }

        return $result->set(new ComponentContext($context));
    }

    /**
     * Build page FQCN => model FQCN map from graph-inferred data.
     *
     * @param  list<string>  $resourceClasses
     * @return array<string, ?string>
     */
    protected function resolveInferredPageModels(
        array $resourceClasses,
        ?ResourceModels $resourceModels,
        ?ResourcePages $resourcePages,
    ): array {
        $map = [];
        foreach ($resourceClasses as $resourceClass) {
            $model = $resourceModels?->get($resourceClass);
            $pages = $resourcePages?->get($resourceClass) ?? [];

            foreach ($pages as $pageClass) {
                $map[$pageClass] = $model;
            }

            if (! isset($map[$resourceClass])) {
                $map[$resourceClass] = $model;
            }
        }

        return $map;
    }

    /**
     * @param  list<string>  $resourceClasses
     * @return array<string, ?string>
     */
    protected function resolvePageModels(
        ?ExplicitAnnotations $explicit,
        array $resourceClasses,
        ?ResourceModels $resourceModels,
        ?ResourcePages $resourcePages,
    ): array {
        if ($explicit !== null && $explicit->pageModels !== []) {
            return $explicit->pageModels;
        }

        return $this->resolveInferredPageModels($resourceClasses, $resourceModels, $resourcePages);
    }

    /**
     * @param  list<string>  $resourceClasses
     * @return array<string, ?string>
     */
    protected function reduceResourceModels(array $resourceClasses, ?ResourceModels $resourceModels): array
    {
        return array_reduce($resourceClasses, function (array $map, string $resource) use ($resourceModels) {
            $map[$resource] = $resourceModels?->get($resource);

            return $map;
        }, []);
    }

    /**
     * @param  list<string>  $resourceClasses
     * @return array<string, list<string>>
     */
    protected function reduceResourcePages(array $resourceClasses, ?ResourcePages $resourcePages): array
    {
        return array_reduce($resourceClasses, function (array $map, string $resource) use ($resourcePages) {
            $map[$resource] = array_values($resourcePages?->get($resource) ?? []);

            return $map;
        }, []);
    }

    /**
     * @param  list<string>  $resourceClasses
     * @return list<ComponentTag>
     */
    protected function computeTags(
        string $componentClass,
        ?ComponentDeclaration $declaration,
        array $resourceClasses,
    ): array {
        $tags = $this->resolveTypeTags($componentClass);

        if ($declaration?->parentResource !== null) {
            $tags[] = ComponentTag::Nested;
        }

        if ($declaration?->cluster !== null) {
            $tags[] = ComponentTag::Clustered;
        }

        if (count($resourceClasses) > 1) {
            $tags[] = ComponentTag::Shared;
        }

        return $tags;
    }

    /** @return list<ComponentTag> */
    protected function resolveTypeTags(string $componentClass): array
    {
        if (! $this->reflectionProvider->hasClass($componentClass)) {
            return [ComponentTag::Component];
        }

        $classReflection = $this->reflectionProvider->getClass($componentClass);

        // Primary type checks (first match wins)
        $primaryChecks = [
            FC::RESOURCE => ComponentTag::Resource,
            FC::RELATION_MANAGER => ComponentTag::RelationManager,
        ];

        foreach ($primaryChecks as $baseClass => $tag) {
            if ($this->isSubclassOfOrSame($classReflection, $baseClass)) {
                return [$tag];
            }
        }

        // Page subtypes — checked before generic Page so we get both tags
        $pageSubtypes = [
            FC::EDIT_RECORD => ComponentTag::EditPage,
            FC::CREATE_RECORD => ComponentTag::CreatePage,
            FC::LIST_RECORDS => ComponentTag::ListRecords,
            FC::VIEW_RECORD => ComponentTag::ViewRecord,
            FC::MANAGE_RELATED_RECORDS => ComponentTag::ManageRelatedRecords,
        ];

        foreach ($pageSubtypes as $baseClass => $tag) {
            if ($this->isSubclassOfOrSame($classReflection, $baseClass)) {
                return [ComponentTag::Page, $tag];
            }
        }

        // Generic page (no subtype match)
        $pageChecks = [
            FC::RESOURCE_PAGE,
            FC::PAGE,
        ];

        foreach ($pageChecks as $baseClass) {
            if ($this->isSubclassOfOrSame($classReflection, $baseClass)) {
                return [ComponentTag::Page];
            }
        }

        return [ComponentTag::Component];
    }

    protected function isSubclassOfOrSame(
        ClassReflection $classReflection,
        string $baseClass,
    ): bool {
        if (! $this->reflectionProvider->hasClass($baseClass)) {
            return false;
        }

        $baseReflection = $this->reflectionProvider->getClass($baseClass);

        return $classReflection->getName() === $baseReflection->getName()
            || $classReflection->isSubclassOfClass($baseReflection);
    }
}
