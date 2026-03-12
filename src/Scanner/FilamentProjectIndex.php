<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner;

use ImSuperlative\PhpstanFilament\Data\FileMetadata;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceModels;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourcePages;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceRelations;

final class FilamentProjectIndex
{
    protected ?ProjectScanResult $scanResult = null;

    public function __construct(
        protected FilamentProjectScanner $scanner,
    ) {}

    protected function scanResult(): ProjectScanResult
    {
        return $this->scanResult ??= $this->scanner->scan();
    }

    /** @return array<string, FileMetadata> */
    public function getIndex(): array
    {
        return $this->scanResult()->index;
    }

    /** @return list<string> */
    public function getResourcesForComponent(string $componentClass): array
    {
        return $this->scanResult()->get(ComponentToResources::class)?->get($componentClass) ?? [];
    }

    /** @return list<string> */
    public function getPagesForResource(string $resourceClass): array
    {
        return array_values($this->scanResult()->get(ResourcePages::class)?->get($resourceClass) ?? []);
    }

    /** @return array<string, string> slug => page FQCN */
    public function getPageMapForResource(string $resourceClass): array
    {
        return $this->scanResult()->get(ResourcePages::class)?->get($resourceClass) ?? [];
    }

    /** @return list<string> */
    public function getRelationsForResource(string $resourceClass): array
    {
        return $this->scanResult()->get(ResourceRelations::class)?->get($resourceClass) ?? [];
    }

    public function getModelForResource(string $resourceClass): ?string
    {
        return $this->scanResult()->get(ResourceModels::class)?->get($resourceClass);
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T|null
     */
    public function get(string $class): ?object
    {
        return $this->scanResult()->get($class);
    }

    /** @param  class-string  $class */
    public function has(string $class): bool
    {
        return $this->scanResult()->has($class);
    }
}
