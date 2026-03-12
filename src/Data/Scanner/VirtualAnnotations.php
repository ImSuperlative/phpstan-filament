<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

use ImSuperlative\PhpstanFilament\Data\FilamentPageAnnotation;

/**
 * Component FQCN => list of page annotations.
 */
final readonly class VirtualAnnotations
{
    /** @use HasTypedMap<class-string, list<FilamentPageAnnotation>> */
    use HasTypedMap;

    /** @param  array<class-string, list<FilamentPageAnnotation>>  $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
