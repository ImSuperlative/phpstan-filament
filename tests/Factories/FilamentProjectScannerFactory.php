<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Tests\Factories;

use ImSuperlative\PhpstanFilament\Scanner\Indexing\ProjectIndexer;

interface FilamentProjectScannerFactory
{
    /**
     * @param  list<string>  $filamentPaths
     * @param  list<string>  $analysedPaths
     */
    public function create(
        array $filamentPaths,
        array $analysedPaths,
    ): ProjectIndexer;
}