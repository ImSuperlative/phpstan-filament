<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Tests\Support;

use ImSuperlative\PhpstanFilament\Scanner\FilamentProjectScanner;

interface FilamentProjectScannerFactory
{
    /**
     * @param  list<string>  $filamentPaths
     * @param  list<string>  $analysedPaths
     */
    public function create(
        array $filamentPaths,
        array $analysedPaths,
    ): FilamentProjectScanner;
}
