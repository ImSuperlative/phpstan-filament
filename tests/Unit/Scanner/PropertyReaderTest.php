<?php

use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentDeclarations;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Scanner\Indexing\PropertyReader;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;

function getPropertyReader(): PropertyReader
{
    $container = PhpstanTestCase::getContainer();

    return $container->getByType(PropertyReader::class);
}

it('reads $relationship from relation managers', function () {
    $result = new ProjectScanResult(index: [], roots: []);
    $result->set(new ComponentToResources([
        'Fixtures\App\Resources\Post\RelationManagers\CommentsRelationManager' => [
            'Fixtures\App\Resources\Post\PostResource',
        ],
    ]));

    $enriched = getPropertyReader()->read($result);
    $declarations = $enriched->get(ComponentDeclarations::class);

    $decl = $declarations->get('Fixtures\App\Resources\Post\RelationManagers\CommentsRelationManager');

    expect($decl)->not->toBeNull()
        ->and($decl->relationshipName)->toBe('comments');
});

it('reads $resource from pages that declare it', function () {
    $result = new ProjectScanResult(index: [], roots: []);
    $result->set(new ComponentToResources([
        'Fixtures\App\Resources\Post\Pages\ListPosts' => [
            'Fixtures\App\Resources\Post\PostResource',
        ],
    ]));

    $enriched = getPropertyReader()->read($result);
    $declarations = $enriched->get(ComponentDeclarations::class);

    $decl = $declarations->get('Fixtures\App\Resources\Post\Pages\ListPosts');

    expect($decl)->not->toBeNull()
        ->and($decl->resourceClass)->toBe('Fixtures\App\Resources\Post\PostResource');
});

it('returns empty declarations when no components', function () {
    $result = new ProjectScanResult(index: [], roots: []);
    $result->set(new ComponentToResources([]));

    $enriched = getPropertyReader()->read($result);
    $declarations = $enriched->get(ComponentDeclarations::class);

    expect($declarations->all())->toBe([]);
});

it('handles missing ComponentToResources', function () {
    $result = new ProjectScanResult(index: [], roots: []);

    $enriched = getPropertyReader()->read($result);
    $declarations = $enriched->get(ComponentDeclarations::class);

    expect($declarations->all())->toBe([]);
});

it('skips classes that do not exist in reflection', function () {
    $result = new ProjectScanResult(index: [], roots: []);
    $result->set(new ComponentToResources([
        'App\NonExistent\FakeRelationManager' => ['App\FakeResource'],
    ]));

    $enriched = getPropertyReader()->read($result);
    $declarations = $enriched->get(ComponentDeclarations::class);

    expect($declarations->all())->toBe([]);
});

it('reads $model from resource classes', function () {
    $result = new ProjectScanResult(index: [], roots: []);
    $result->set(new ComponentToResources([
        'Fixtures\App\Resources\Post\PostResource' => ['Fixtures\App\Resources\Post\PostResource'],
    ]));

    $enriched = getPropertyReader()->read($result);
    $declarations = $enriched->get(ComponentDeclarations::class);

    $decl = $declarations->get('Fixtures\App\Resources\Post\PostResource');

    expect($decl)->not->toBeNull()
        ->and($decl->model)->toBe('Fixtures\App\Models\Post');
});
