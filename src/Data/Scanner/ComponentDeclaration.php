<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

final readonly class ComponentDeclaration
{
    public function __construct(
        public ?string $model = null,
        public ?string $resourceClass = null,
        public ?string $relatedResourceClass = null,
        public ?string $relationshipName = null,
        public ?string $cluster = null,
        public ?string $parentResource = null,
    ) {}
}
