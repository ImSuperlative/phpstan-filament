<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Resolvers;

use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentNode;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentTag;

final class ComponentProvider
{
    public function __construct(
        protected ComponentNode $node,
    ) {}

    /**
     * Single model when unambiguous, null otherwise.
     */
    public function getModel(): ?string
    {
        if ($this->node->explicitModel !== null) {
            return $this->node->explicitModel;
        }

        $models = $this->getModelClasses();

        return count($models) === 1 ? $models[0] : null;
    }

    /** @return list<string> */
    public function getModelClasses(): array
    {
        if ($this->node->explicitModel !== null) {
            return [$this->node->explicitModel];
        }

        return array_values(array_unique(array_filter($this->node->pageModels)));
    }

    public function getModelForPage(string $page): ?string
    {
        return $this->node->pageModels[$page] ?? null;
    }

    public function getModelForResource(string $resource): ?string
    {
        return $this->node->resourceModels[$resource] ?? null;
    }

    /** @return list<string> */
    public function getPagesForResource(string $resource): array
    {
        return $this->node->resourcePages[$resource] ?? [];
    }

    /** @return list<string> */
    public function getPages(): array
    {
        return array_keys($this->node->pageModels);
    }

    /** @return list<string> */
    public function getResources(): array
    {
        return array_keys($this->node->resourceModels);
    }

    /** @return list<string> */
    public function getOwningResources(): array
    {
        return $this->node->owningResources;
    }

    public function getResourceClass(): ?string
    {
        return $this->node->declaration->resourceClass;
    }

    public function getRelatedResourceClass(): ?string
    {
        return $this->node->declaration->relatedResourceClass;
    }

    public function getRelationshipName(): ?string
    {
        return $this->node->declaration->relationshipName;
    }

    public function hasTag(ComponentTag $tag): bool
    {
        return in_array($tag, $this->node->tags, true);
    }

    /** @param list<ComponentTag> $tags */
    public function hasAnyTag(array $tags): bool
    {
        return array_any($tags, fn (ComponentTag $tag) => in_array($tag, $this->node->tags, true));
    }

    public function isNested(): bool
    {
        return $this->hasTag(ComponentTag::Nested)
            || $this->hasTag(ComponentTag::ManageRelatedRecords);
    }

    public function getOwnerModel(): ?string
    {
        $resourceClass = $this->node->declaration->resourceClass;

        if ($resourceClass !== null) {
            return $this->node->resourceModels[$resourceClass] ?? null;
        }

        foreach ($this->node->owningResources as $resource) {
            $model = $this->node->resourceModels[$resource] ?? null;
            if ($model !== null) {
                return $model;
            }
        }

        return null;
    }
}
