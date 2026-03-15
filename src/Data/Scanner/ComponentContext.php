<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

/**
 * Component FQCN => resolved context (model + pages).
 * Replaces VirtualAnnotations.
 */
final readonly class ComponentContext
{
    /** @use HasTypedMap<class-string, ComponentNode> */
    use HasTypedMap;

    /** @param array<class-string, ComponentNode> $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
