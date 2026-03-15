<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner\Transformers;

use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;

interface ScanTransformer
{
    public function transform(ProjectScanResult $result): ProjectScanResult;
}
