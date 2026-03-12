<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

/**
 * Resource FQCN => model FQCN.
 */
final readonly class ResourceModels
{
    /** @use HasTypedMap<class-string, class-string> */
    use HasTypedMap;

    /** @param  array<class-string, class-string>  $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
