<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

/**
 * Resource FQCN => [slug => page FQCN].
 */
final readonly class ResourcePages
{
    /** @use HasTypedMap<class-string, array<string, class-string>> */
    use HasTypedMap;

    /** @param  array<class-string, array<string, class-string>>  $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
