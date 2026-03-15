<?php

use ImSuperlative\PhpstanFilament\Data\Scanner\ResourcePages;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\Graph\PagesTransformer;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Tests\Factories\FilamentProjectScannerFactory;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;

function getBaseResult(): ProjectScanResult
{
    return PhpstanTestCase::getContainer()
        ->getByType(FilamentProjectScannerFactory::class)
        ->create(
            filamentPaths: [],
            analysedPaths: [tests_path('Fixtures')],
        )
        ->index();
}

it('parses getPages from PostResource', function () {
    $result = getBaseResult();

    $transformer = new PagesTransformer(
        PhpstanTestCase::getContainer()->getByType(FileParser::class),
    );

    $enriched = $transformer->transform($result);
    $pages = $enriched->get(ResourcePages::class);

    expect($pages)->not->toBeNull()
        ->and($pages->has('Fixtures\App\Resources\Post\PostResource'))->toBeTrue()
        ->and($pages->get('Fixtures\App\Resources\Post\PostResource'))
        ->toHaveKey('index')
        ->toHaveKey('create')
        ->toHaveKey('edit')
        ->toHaveKey('view');

    expect($pages->get('Fixtures\App\Resources\Post\PostResource')['index'])
        ->toBe('Fixtures\App\Resources\Post\Pages\ListPosts');
});

it('parses getPages from CommentResource', function () {
    $result = getBaseResult();

    $transformer = new PagesTransformer(
        PhpstanTestCase::getContainer()->getByType(FileParser::class),
    );

    $enriched = $transformer->transform($result);
    $pages = $enriched->get(ResourcePages::class);

    expect($pages)->not->toBeNull()
        ->and($pages->has('Fixtures\App\Resources\Comment\CommentResource'))->toBeTrue()
        ->and($pages->get('Fixtures\App\Resources\Comment\CommentResource'))
        ->toHaveKey('edit');
});

it('returns original result for resources without getPages', function () {
    $result = new ProjectScanResult(index: [], roots: []);

    $transformer = new PagesTransformer(
        PhpstanTestCase::getContainer()->getByType(FileParser::class),
    );

    $enriched = $transformer->transform($result);
    $pages = $enriched->get(ResourcePages::class);

    expect($pages)->not->toBeNull()
        ->and($pages->all())->toBe([]);
});
