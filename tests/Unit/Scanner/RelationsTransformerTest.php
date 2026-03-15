<?php

use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceRelations;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\Graph\RelationsTransformer;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Tests\Factories\FilamentProjectScannerFactory;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;

function getBaseResultForRelations(): ProjectScanResult
{
    return PhpstanTestCase::getContainer()
        ->getByType(FilamentProjectScannerFactory::class)
        ->create(
            filamentPaths: [],
            analysedPaths: [tests_path('Fixtures')],
        )
        ->index();
}

it('parses plain class-string relations', function () {
    $result = getBaseResultForRelations();

    $transformer = new RelationsTransformer(
        PhpstanTestCase::getContainer()->getByType(FileParser::class),
    );

    $enriched = $transformer->transform($result);
    $relations = $enriched->get(ResourceRelations::class);

    expect($relations)->not->toBeNull()
        ->and($relations->has('Fixtures\App\Resources\Post\PostResource'))->toBeTrue()
        ->and($relations->get('Fixtures\App\Resources\Post\PostResource'))
        ->toContain('Fixtures\App\Resources\Post\RelationManagers\CommentsRelationManager');
});

it('unwraps RelationGroup::make to extract nested managers', function () {
    $result = getBaseResultForRelations();

    $transformer = new RelationsTransformer(
        PhpstanTestCase::getContainer()->getByType(FileParser::class),
    );

    $enriched = $transformer->transform($result);
    $postRelations = $enriched->get(ResourceRelations::class)->get('Fixtures\App\Resources\Post\PostResource');

    // Should contain all 3: plain + 2 from RelationGroup
    expect($postRelations)
        ->toContain('Fixtures\App\Resources\Post\RelationManagers\CommentsRelationManager')
        ->toContain('Fixtures\App\Resources\Post\RelationManagers\TagsRelationManager')
        ->toContain('Fixtures\App\Resources\Post\RelationManagers\MediaRelationManager')
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
