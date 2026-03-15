<?php

use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Scanner\FilamentProjectScanner;
use ImSuperlative\PhpstanFilament\Scanner\Indexing\ProjectIndexer;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Tests\Factories\FilamentProjectScannerFactory;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;

function getIndexer(): ProjectIndexer
{
    return PhpstanTestCase::getContainer()
        ->getByType(FilamentProjectScannerFactory::class)
        ->create(
            filamentPaths: [],
            analysedPaths: [tests_path('Fixtures')],
        );
}

function getFullScanResult(): ProjectScanResult
{
    return PhpstanTestCase::getContainer()
        ->getByType(FilamentProjectScanner::class)
        ->scan();
}

it('scans and returns a ProjectScanResult', function () {
    $result = getFullScanResult();

    expect($result)->toBeInstanceOf(ProjectScanResult::class)
        ->and($result->index)->not->toBeEmpty()
        ->and($result->roots)->not->toBeEmpty();
});

it('discovers files with Filament imports', function () {
    $indexer = getIndexer();
    $method = new ReflectionMethod($indexer, 'discoverFilamentFiles');
    $filePaths = $method->invoke($indexer);

    foreach ($filePaths as $filePath) {
        expect(file_get_contents($filePath))->toContain('use Filament\\');
    }

    expect($filePaths)->not->toBeEmpty();
});

it('identifies resource classes as roots', function () {
    $result = getIndexer()->index();

    expect($result->roots)->not->toBeEmpty();

    foreach ($result->roots as $filePath) {
        expect($result->index[$filePath]->extends)->toBeIn([
            'Filament\\Resources\\Resource',
            'Filament\\Resources\\RelationManagers\\RelationManager',
            'Filament\\Resources\\Pages\\ManageRelatedRecords',
        ]);
    }
});

it('builds component to resources map via graph walk', function () {
    $result = getFullScanResult();
    $componentToResources = $result->get(ComponentToResources::class);

    expect($componentToResources)->not->toBeNull()
        ->and($componentToResources->has('Fixtures\App\Resources\Post\Schemas\PostForm'))->toBeTrue()
        ->and($componentToResources->get('Fixtures\App\Resources\Post\Schemas\PostForm'))
        ->toContain('Fixtures\App\Resources\Post\PostResource');
});

it('runs graph transformers before component discovery', function () {
    $result = getFullScanResult();

    expect($result->index)->not->toBeEmpty()
        ->and($result->roots)->not->toBeEmpty()
        ->and($result->get(ComponentToResources::class))->not->toBeNull();
});
