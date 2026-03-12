<?php

/** @noinspection ClassConstantCanBeUsedInspection */

use ImSuperlative\PhpstanFilament\Resolvers\VirtualAnnotationProvider;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;
use ImSuperlative\PhpstanFilament\Tests\Support\VirtualAnnotationProviderFactory;

function getScanningProvider(): VirtualAnnotationProvider
{
    return PhpstanTestCase::getContainer()
        ->getByType(VirtualAnnotationProviderFactory::class)
        ->create(
            enabled: true,
            warnOnVirtual: false,
            filamentPaths: [],
            analysedPaths: [dirname(__DIR__).'/Fixtures'],
        );
}

it('only discovers files with Filament imports', function () {
    $provider = getScanningProvider();
    $reflection = new ReflectionMethod($provider, 'discoverFilamentFiles');
    $filePaths = $reflection->invoke($provider);

    foreach ($filePaths as $filePath) {
        expect(file_get_contents($filePath))->toContain('use Filament\\');
    }

    expect($filePaths)->not->toBeEmpty();
});

it('indexes file metadata with fully qualified name and resolved extends', function () {
    $provider = getScanningProvider();

    $files = new ReflectionMethod($provider, 'discoverFilamentFiles')->invoke($provider);
    $index = new ReflectionMethod($provider, 'indexFileMetadata')->invoke($provider, $files);

    foreach ($index as $record) {
        expect($record->fullyQualifiedName)->toBeString()->not->toBeEmpty();
    }

    $extendsValues = array_map(fn ($record) => $record->extends, $index);
    expect($extendsValues)->toContain(Filament\Resources\Resource::class);
});

it('identifies resource classes as roots', function () {
    $provider = getScanningProvider();

    $files = new ReflectionMethod($provider, 'discoverFilamentFiles')->invoke($provider);
    $index = new ReflectionMethod($provider, 'indexFileMetadata')->invoke($provider, $files);
    $roots = new ReflectionMethod($provider, 'findResourceRoots')->invoke($provider, $index);

    expect($roots)->not->toBeEmpty();

    foreach ($roots as $filePath) {
        expect($index[$filePath]->extends)->toBeIn([
            'Filament\\Resources\\Resource',
            'Filament\\Resources\\RelationManagers\\RelationManager',
            'Filament\\Resources\\Pages\\ManageRelatedRecords',
        ]);
    }
});

it('builds a class dependency graph from roots via forward walk', function () {
    $provider = getScanningProvider();

    $files = new ReflectionMethod($provider, 'discoverFilamentFiles')->invoke($provider);
    $index = new ReflectionMethod($provider, 'indexFileMetadata')->invoke($provider, $files);
    $classToFile = new ReflectionMethod($provider, 'mapClassNamesToFiles')->invoke($provider, $index);
    $roots = new ReflectionMethod($provider, 'findResourceRoots')->invoke($provider, $index);
    $graph = new ReflectionMethod($provider, 'buildClassDependencyGraph')->invoke($provider, $roots, $index, $classToFile);

    // PostResource should have edges to PostForm and PostTable via ::configure()
    expect($graph)->toHaveKey('Fixtures\App\Resources\PostResource')
        ->and($graph['Fixtures\App\Resources\PostResource'])
        ->toContain('Fixtures\App\Resources\PostResource\Schemas\PostForm')
        ->toContain('Fixtures\App\Resources\PostResource\Schemas\PostTable');
});

it('assigns roots to reachable classes', function () {
    $provider = getScanningProvider();

    $files = new ReflectionMethod($provider, 'discoverFilamentFiles')->invoke($provider);
    $index = new ReflectionMethod($provider, 'indexFileMetadata')->invoke($provider, $files);
    $classToFile = new ReflectionMethod($provider, 'mapClassNamesToFiles')->invoke($provider, $index);
    $roots = new ReflectionMethod($provider, 'findResourceRoots')->invoke($provider, $index);
    $graph = new ReflectionMethod($provider, 'buildClassDependencyGraph')->invoke($provider, $roots, $index, $classToFile);
    $contextMap = new ReflectionMethod($provider, 'assignRootsToReachableClasses')->invoke($provider, $roots, $index, $graph);

    // PostForm should be tagged with PostResource as its root
    expect($contextMap)->toHaveKey('Fixtures\App\Resources\PostResource\Schemas\PostForm')
        ->and($contextMap['Fixtures\App\Resources\PostResource\Schemas\PostForm'])
        ->toContain('Fixtures\App\Resources\PostResource');
});

it('maps class names to files from the index', function () {
    $provider = getScanningProvider();

    $files = new ReflectionMethod($provider, 'discoverFilamentFiles')->invoke($provider);
    $index = new ReflectionMethod($provider, 'indexFileMetadata')->invoke($provider, $files);
    $lookup = new ReflectionMethod($provider, 'mapClassNamesToFiles')->invoke($provider, $index);

    foreach ($lookup as $className => $filePath) {
        expect($index)->toHaveKey($filePath)
            ->and($index[$filePath]->fullyQualifiedName)->toBe($className);
    }
});
