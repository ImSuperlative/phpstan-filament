<?php

/**
 * Outputs the full scanner index as JSON to stdout.
 *
 * Usage:
 *   php script/scanner-tool.php /path/to/project [--config=path/to/config.neon] [--analysed-paths=app,src/Filament]
 */

$projectPath = $argv[1] ?? null;

if ($projectPath === null) {
    fwrite(STDERR, "Usage: php script/scanner-tool.php /path/to/project [--config=...] [--analysed-paths=...]\n");
    exit(1);
}

$projectPath = realpath($projectPath);

if ($projectPath === false) {
    fwrite(STDERR, "Invalid path\n");
    exit(1);
}

// Parse optional args
$configFile = __DIR__.'/script.neon';
$analysedPaths = null;

for ($i = 2; $i < $argc; $i++) {
    if (str_starts_with($argv[$i], '--config=')) {
        $configFile = substr($argv[$i], 9);
    } elseif (str_starts_with($argv[$i], '--analysed-paths=')) {
        $paths = explode(',', substr($argv[$i], 17));
        $analysedPaths = array_map(fn ($p) => str_starts_with($p, '/') ? trim($p) : $projectPath.'/'.trim($p), $paths);
    }
}

if ($analysedPaths === null) {
    $analysedPaths = [$projectPath.'/app'];
}

require dirname(__DIR__).'/vendor/autoload.php';

use ImSuperlative\PhpstanFilament\Scanner\FilamentProjectScanner;
use PHPStan\DependencyInjection\ContainerFactory;

$containerFactory = new ContainerFactory($projectPath);
$container = $containerFactory->create(
    tempDirectory: sys_get_temp_dir().'/phpstan',
    additionalConfigFiles: [$configFile],
    analysedPaths: $analysedPaths,
);

/** @var FilamentProjectScanner $scanner */
$scanner = $container->getByType(FilamentProjectScanner::class);

echo json_encode($scanner->scan());
