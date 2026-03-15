<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner\Transformers;

use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;

interface GraphTransformer extends ScanTransformer
{
    /** @return array<class-string, list<class-string>> resource FQCN => component FQCNs */
    public function componentMappings(ProjectScanResult $result): array;
}
