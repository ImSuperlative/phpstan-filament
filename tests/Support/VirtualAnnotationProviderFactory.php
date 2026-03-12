<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Tests\Support;

use ImSuperlative\PhpstanFilament\Resolvers\VirtualAnnotationProvider;

interface VirtualAnnotationProviderFactory
{
    /**
     * @param  list<string>  $filamentPaths
     * @param  list<string>  $analysedPaths
     */
    public function create(
        bool $enabled,
        bool $warnOnVirtual,
        array $filamentPaths,
        array $analysedPaths
    ): VirtualAnnotationProvider;
}
