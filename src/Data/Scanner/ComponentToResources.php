<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

/**
 * Component FQCN => list of owning resource FQCNs.
 */
final readonly class ComponentToResources
{
    /** @use HasTypedMap<class-string, list<class-string>> */
    use HasTypedMap;

    /** @param  array<class-string, list<class-string>>  $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
