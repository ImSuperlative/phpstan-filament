<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Resolvers\Concerns;

use ImSuperlative\PhpstanFilament\Support\PathResolver;

trait FilamentFileDiscovery
{
    protected const array FILAMENT_PREFIXES = [
        'Filament\\',
        'Illuminate\\',
        'Livewire\\',
    ];

    /**
     * @return list<string>
     */
    protected function discoverPhpFiles(): array
    {
        $files = $this->findPhpFilesInPaths($this->scanPaths());
        $excludePatterns = $this->excludePatterns();

        if ($excludePatterns === []) {
            return $files;
        }

        return array_values(array_filter(
            $files,
            fn (string $file) => ! PathResolver::isExcluded($file, $excludePatterns),
        ));
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    protected function findPhpFilesInPaths(array $paths): array
    {
        /** @var list<string> */
        return array_reduce($paths, function (array $carry, string $path) {
            if (is_file($path)) {
                $carry[] = $path;

                return $carry;
            }

            if (! is_dir($path)) {
                return $carry;
            }

            return [...$carry, ...PathResolver::globRecursive($path, '*.php')];
        }, []);
    }

    /** @return list<string> */
    protected function scanPaths(): array
    {
        if ($this->filamentPaths !== []) {
            return PathResolver::includePaths($this->filamentPaths, $this->currentWorkingDirectory);
        }

        return array_values(array_unique(
            [...$this->analysedPaths, ...$this->analysedPathsFromConfig]
        ));
    }

    /** @return list<string> */
    protected function excludePatterns(): array
    {
        if ($this->filamentPaths === []) {
            return [];
        }

        return PathResolver::excludePatterns($this->filamentPaths, $this->currentWorkingDirectory);
    }

    protected function isFilamentClass(string $className): bool
    {
        return array_any(
            self::FILAMENT_PREFIXES,
            fn (string $prefix) => str_starts_with($className, $prefix)
        );
    }
}
