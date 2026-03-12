<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

/**
 * Resource FQCN => list of relation manager FQCNs.
 */
final readonly class ResourceRelations
{
    /** @use HasTypedMap<class-string, list<class-string>> */
    use HasTypedMap;

    /** @param  array<class-string, list<class-string>>  $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
