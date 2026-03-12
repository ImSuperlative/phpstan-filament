<?php

use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Scanner\FilamentProjectScanner;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;
use ImSuperlative\PhpstanFilament\Tests\Support\FilamentProjectScannerFactory;

function getScanner(): FilamentProjectScanner
{
    return PhpstanTestCase::getContainer()
        ->getByType(FilamentProjectScannerFactory::class)
        ->create(
            filamentPaths: [],
            analysedPaths: [dirname(__DIR__, 2).'/Fixtures'],
        );
}

it('scans and returns a ProjectScanResult', function () {
    $result = getScanner()->scan();

    expect($result)->toBeInstanceOf(ProjectScanResult::class)
        ->and($result->index)->not->toBeEmpty()
        ->and($result->roots)->not->toBeEmpty();
});

it('discovers files with Filament imports', function () {
    $scanner = getScanner();
    $method = new ReflectionMethod($scanner, 'discoverFilamentFiles');
    $filePaths = $method->invoke($scanner);

    foreach ($filePaths as $filePath) {
        expect(file_get_contents($filePath))->toContain('use Filament\\');
    }

    expect($filePaths)->not->toBeEmpty();
});

it('identifies resource classes as roots', function () {
    $result = getScanner()->scan();

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
    $result = getScanner()->scan();
    $componentToResources = $result->get(ComponentToResources::class);

    expect($componentToResources)->not->toBeNull()
        ->and($componentToResources->has('Fixtures\App\Resources\PostResource\Schemas\PostForm'))->toBeTrue()
        ->and($componentToResources->get('Fixtures\App\Resources\PostResource\Schemas\PostForm'))
        ->toContain('Fixtures\App\Resources\PostResource');
});

it('runs graph transformers before component discovery', function () {
    $result = getScanner()->scan();

    expect($result->index)->not->toBeEmpty()
        ->and($result->roots)->not->toBeEmpty()
        ->and($result->get(ComponentToResources::class))->not->toBeNull();
});
