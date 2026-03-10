<?php

use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\DiscoveredClassCache;

require dirname(__DIR__).'/vendor/autoload.php';

define('PHPSTAN_FILAMENT_TEST_CACHE', true);

/*
 * Pre-scan vendor classmap for Filament classes that override
 * resolveDefaultClosureDependencyForEvaluationByName. Writes result
 * to a temp file so InjectionMapFactory skips the expensive scan
 * on every container rebuild.
 */
$classmap = dirname(__DIR__).'/vendor/composer/autoload_classmap.php';

if (file_exists($classmap)) {
    $method = 'resolveDefaultClosureDependencyForEvaluationByName';
    $candidates = [];

    foreach (require $classmap as $class => $file) {
        if (
            $class === 'Filament\\Support\\Concerns\\EvaluatesClosures'
            || ! str_starts_with($class, 'Filament\\')
        ) {
            continue;
        }

        if (str_contains((string) file_get_contents($file), $method)) {
            $candidates[$class] = $file;
        }
    }

    DiscoveredClassCache::writeCacheFile($candidates);
}
