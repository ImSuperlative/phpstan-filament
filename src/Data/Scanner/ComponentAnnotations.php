<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

/**
 * Component FQCN => explicit annotations found on the class (PHPDoc + attributes).
 */
final readonly class ComponentAnnotations
{
    /** @use HasTypedMap<class-string, ExplicitAnnotations> */
    use HasTypedMap;

    /** @param array<class-string, ExplicitAnnotations> $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
