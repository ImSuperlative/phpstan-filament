<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

final readonly class ComponentNode
{
    /**
     * @param  list<ComponentTag>  $tags
     * @param  array<string, ?string>  $pageModels  page FQCN => model FQCN|null
     * @param  array<string, ?string>  $resourceModels  resource FQCN => model FQCN|null
     * @param  array<string, list<string>>  $resourcePages  resource FQCN => list of page FQCNs
     * @param  list<string>  $owningResources  resource FQCNs that own this component
     */
    public function __construct(
        public array $tags = [],
        public ?string $explicitModel = null,
        public array $pageModels = [],
        public array $resourceModels = [],
        public array $resourcePages = [],
        public array $owningResources = [],
        public ComponentDeclaration $declaration = new ComponentDeclaration,
    ) {}
}
