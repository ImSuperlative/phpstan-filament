<?php

use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceRelations;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\RelationsTransformer;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;
use ImSuperlative\PhpstanFilament\Tests\Support\FilamentProjectScannerFactory;

function getScannerForRelations(): \ImSuperlative\PhpstanFilament\Scanner\FilamentProjectScanner
{
    return PhpstanTestCase::getContainer()
        ->getByType(FilamentProjectScannerFactory::class)
        ->create(
            filamentPaths: [],
            analysedPaths: [dirname(__DIR__, 2).'/Fixtures'],
        );
}

function getBaseResultForRelations(): ProjectScanResult
{
    $scanner = getScannerForRelations();
    $discover = new ReflectionMethod($scanner, 'discoverFilamentFiles');
    $indexMethod = new ReflectionMethod($scanner, 'indexFileMetadata');
    $rootsMethod = new ReflectionMethod($scanner, 'findResourceRoots');

    $filePaths = $discover->invoke($scanner);
    $index = $indexMethod->invoke($scanner, $filePaths);
    $roots = $rootsMethod->invoke($scanner, $index);

    return new ProjectScanResult(index: $index, roots: $roots);
}

it('parses plain class-string relations', function () {
    $result = getBaseResultForRelations();

    $transformer = new RelationsTransformer(
        PhpstanTestCase::getContainer()->getByType(FileParser::class),
    );

    $enriched = $transformer->transform($result);
    $relations = $enriched->get(ResourceRelations::class);

    expect($relations)->not->toBeNull()
        ->and($relations->has('Fixtures\App\Resources\PostResource'))->toBeTrue()
        ->and($relations->get('Fixtures\App\Resources\PostResource'))
        ->toContain('Fixtures\App\Resources\PostResource\RelationManagers\CommentsRelationManager');
});

it('unwraps RelationGroup::make to extract nested managers', function () {
    $result = getBaseResultForRelations();

    $transformer = new RelationsTransformer(
        PhpstanTestCase::getContainer()->getByType(FileParser::class),
    );

    $enriched = $transformer->transform($result);
    $postRelations = $enriched->get(ResourceRelations::class)->get('Fixtures\App\Resources\PostResource');

    // Should contain all 3: plain + 2 from RelationGroup
    expect($postRelations)
        ->toContain('Fixtures\App\Resources\PostResource\RelationManagers\CommentsRelationManager')
        ->toContain('Fixtures\App\Resources\PostResource\RelationManagers\TagsRelationManager')
        ->toContain('Fixtures\App\Resources\PostResource\RelationManagers\MediaRelationManager')
        ->toHaveCount(3);
});

it('returns empty for resources without getRelations', function () {
    $result = new ProjectScanResult(index: [], roots: []);

    $transformer = new RelationsTransformer(
        PhpstanTestCase::getContainer()->getByType(FileParser::class),
    );

    $enriched = $transformer->transform($result);
    $relations = $enriched->get(ResourceRelations::class);

    expect($relations)->not->toBeNull()
        ->and($relations->all())->toBe([]);
});
