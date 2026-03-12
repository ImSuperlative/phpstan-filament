<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner;

interface EnrichmentTransformer
{
    public function transform(ProjectScanResult $result): ProjectScanResult;
}
