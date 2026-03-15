<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Support;

final class PathResolver
{
    /**
     * Partition config paths into absolute include paths and exclusion patterns.
     *
     * @param  list<string>  $paths
     * @return array{include: list<string>, exclude: list<string>}
     */
    public static function partition(array $paths, string $cwd): array
    {
        /** @var array{include: list<string>, exclude: list<string>} */
        return array_reduce($paths, function (array $carry, string $path) use ($cwd) {
            if (self::isExclusion($path)) {
                $carry['exclude'][] = self::toAbsolute(self::stripExclusionPrefix($path), $cwd);
            } else {
                $carry['include'][] = self::toAbsolute($path, $cwd);
            }

            return $carry;
        }, ['include' => [], 'exclude' => []]);
    }

    /**
     * Extract include paths from config, ignoring `!` exclusions.
     *
     * @param  list<string>  $paths
     * @return list<string> absolute paths to scan
     */
    public static function includePaths(array $paths, string $cwd): array
    {
        return self::partition($paths, $cwd)['include'];
    }

    /**
     * Extract exclusion patterns from config (entries prefixed with `!`).
     *
     * @param  list<string>  $paths
     * @return list<string> absolute exclusion patterns
     */
    public static function excludePatterns(array $paths, string $cwd): array
    {
        return self::partition($paths, $cwd)['exclude'];
    }

    /**
     * Check if a file path matches any exclusion pattern.
     *
     * Supports exact files, directory prefixes, and glob wildcards.
     *
     * @param  list<string>  $patterns
     */
    public static function isExcluded(string $filePath, array $patterns): bool
    {
        return array_any(
            $patterns,
            fn (string $pattern) => self::matchesPattern($filePath, $pattern),
        );
    }

    /**
     * Recursively find files matching a pattern in a directory.
     *
     * @return list<string>
     */
    public static function globRecursive(string $directory, string $pattern): array
    {
        $files = glob($directory.'/'.$pattern) ?: [];

        foreach (glob($directory.'/*', GLOB_ONLYDIR | GLOB_NOSORT) ?: [] as $subdirectory) {
            $files = [...$files, ...self::globRecursive($subdirectory, $pattern)];
        }

        return $files;
    }

    protected static function matchesPattern(string $filePath, string $pattern): bool
    {
        return self::isGlobPattern($pattern)
            ? fnmatch($pattern, $filePath)
            : self::isWithinDirectory($filePath, $pattern);
    }

    protected static function isWithinDirectory(string $filePath, string $directory): bool
    {
        return $filePath === $directory || str_starts_with($filePath, $directory.'/');
    }

    protected static function isGlobPattern(string $pattern): bool
    {
        return str_contains($pattern, '*') || str_contains($pattern, '?');
    }

    protected static function isExclusion(string $path): bool
    {
        return str_starts_with($path, '!');
    }

    protected static function stripExclusionPrefix(string $path): string
    {
        return substr($path, 1);
    }

    protected static function toAbsolute(string $path, string $cwd): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        $resolved = $cwd.'/'.$path;

        if (is_dir($resolved) || is_file($resolved)) {
            return $resolved;
        }

        $fallback = getcwd();
        if ($fallback === false) {
            return $resolved;
        }

        $cwdResolved = $fallback.'/'.$path;

        return (is_dir($cwdResolved) || is_file($cwdResolved)) ? $cwdResolved : $resolved;
    }
}
