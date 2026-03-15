<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

/**
 * Class FQCN => list of dependency FQCNs (static calls, extends, traits).
 */
final readonly class DependencyGraph
{
    /** @use HasTypedMap<class-string, list<class-string>> */
    use HasTypedMap;

    /** @param  array<class-string, list<class-string>>  $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
