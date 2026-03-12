<?php

use ImSuperlative\PhpstanFilament\Data\Scanner\ResourcePages;
use ImSuperlative\PhpstanFilament\Scanner\FilamentProjectScanner;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\PagesTransformer;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;
use ImSuperlative\PhpstanFilament\Tests\Support\FilamentProjectScannerFactory;

function getScannerForPages(): FilamentProjectScanner
{
    return PhpstanTestCase::getContainer()
        ->getByType(FilamentProjectScannerFactory::class)
        ->create(
            filamentPaths: [],
            analysedPaths: [dirname(__DIR__, 2).'/Fixtures'],
        );
}

function getBaseResult(): ProjectScanResult
{
    $scanner = getScannerForPages();
    $discover = new ReflectionMethod($scanner, 'discoverFilamentFiles');
    $indexMethod = new ReflectionMethod($scanner, 'indexFileMetadata');
    $rootsMethod = new ReflectionMethod($scanner, 'findResourceRoots');

    $filePaths = $discover->invoke($scanner);
    $index = $indexMethod->invoke($scanner, $filePaths);
    $roots = $rootsMethod->invoke($scanner, $index);

    return new ProjectScanResult(index: $index, roots: $roots);
}

it('parses getPages from PostResource', function () {
    $result = getBaseResult();

    $transformer = new PagesTransformer(
        PhpstanTestCase::getContainer()->getByType(FileParser::class),
    );

    $enriched = $transformer->transform($result);
    $pages = $enriched->get(ResourcePages::class);

    expect($pages)->not->toBeNull()
        ->and($pages->has('Fixtures\App\Resources\PostResource'))->toBeTrue()
        ->and($pages->get('Fixtures\App\Resources\PostResource'))
        ->toHaveKey('index')
        ->toHaveKey('create')
        ->toHaveKey('edit')
        ->toHaveKey('view');

    expect($pages->get('Fixtures\App\Resources\PostResource')['index'])
        ->toBe('Fixtures\App\Resources\PostResource\Pages\ListPosts');
});

it('parses getPages from CommentResource', function () {
    $result = getBaseResult();

    $transformer = new PagesTransformer(
        PhpstanTestCase::getContainer()->getByType(FileParser::class),
    );

    $enriched = $transformer->transform($result);
    $pages = $enriched->get(ResourcePages::class);

    expect($pages)->not->toBeNull()
        ->and($pages->has('Fixtures\App\Resources\CommentResource'))->toBeTrue()
        ->and($pages->get('Fixtures\App\Resources\CommentResource'))
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
