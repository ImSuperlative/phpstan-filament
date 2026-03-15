<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner;

use Closure;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\ScanTransformer;

class ScanPipeline
{
    protected function __construct(
        protected ProjectScanResult $passable,
    ) {}

    public static function send(ProjectScanResult $passable): self
    {
        return new self($passable);
    }

    /** @param list<ScanTransformer> $transformers */
    public function through(array $transformers): static
    {
        foreach ($transformers as $transformer) {
            $this->passable = $transformer->transform($this->passable);
        }

        return $this;
    }

    /** @param Closure(ProjectScanResult): ProjectScanResult $callback */
    public function then(Closure $callback): static
    {
        $this->passable = $callback($this->passable);

        return $this;
    }

    public function thenReturn(): ProjectScanResult
    {
        return $this->passable;
    }
}
