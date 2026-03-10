<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Rules\ClosureInjection;

/**
 * No-op in production. In tests, the bootstrap pre-scans vendor
 * classes and writes the result to a cache file. This avoids
 * repeated file scanning across container rebuilds.
 */
class DiscoveredClassCache
{
    protected const string CACHE_FILE = 'discovered-classes.php';

    public function isRunningTest(): bool
    {
        return defined('PHPSTAN_FILAMENT_TEST_CACHE');
    }

    /**
     * @return array<class-string, string> className => filePath
     */
    public function get(): array
    {
        $path = self::cachePath();

        if (! file_exists($path)) {
            return [];
        }

        /** @var array<class-string, string> */
        return require $path;
    }

    /**
     * @param  array<class-string, string>  $classes
     */
    public static function writeCacheFile(array $classes): void
    {
        $dir = dirname(self::cachePath());

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(self::cachePath(), '<?php return '.var_export($classes, true).';');
    }

    protected static function cachePath(): string
    {
        return dirname(__DIR__, 3).'/tests/cache/'.self::CACHE_FILE;
    }
}
