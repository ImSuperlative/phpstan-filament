<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner;

use ImSuperlative\PhpstanFilament\Scanner\Indexing\ComponentDiscovery;
use ImSuperlative\PhpstanFilament\Scanner\Indexing\ProjectIndexer;
use ImSuperlative\PhpstanFilament\Scanner\Indexing\PropertyReader;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\EnrichmentTransformer;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\GraphTransformer;

class FilamentProjectScanner
{
    /**
     * @param  list<GraphTransformer>  $graphTransformers
     * @param  list<EnrichmentTransformer>  $enrichmentTransformers
     */
    public function __construct(
        protected ProjectIndexer $projectIndexer,
        protected array $graphTransformers,
        protected array $enrichmentTransformers,
        protected PropertyReader $propertyReader,
        protected ComponentDiscovery $componentDiscovery,
    ) {}

    public function scan(): ProjectScanResult
    {
        return ScanPipeline::send($this->projectIndexer->index())
            ->through($this->graphTransformers)
            ->then(fn (ProjectScanResult $r) => $this->componentDiscovery->discover($r))
            ->then(fn (ProjectScanResult $r) => $this->propertyReader->read($r))
            ->through($this->enrichmentTransformers)
            ->thenReturn();
    }
}
