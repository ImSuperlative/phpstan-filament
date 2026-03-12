<?php

use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceModels;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\ModelTransformer;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;
use ImSuperlative\PhpstanFilament\Tests\Support\FilamentProjectScannerFactory;
use PHPStan\Reflection\ReflectionProvider;

function getModelTransformer(): ModelTransformer
{
    $container = PhpstanTestCase::getContainer();

    return new ModelTransformer(
        $container->getByType(ReflectionProvider::class),
        $container->getByType(FileParser::class),
        $container->getByType(FilamentClassHelper::class),
    );
}

function getBaseResultForModels(): ProjectScanResult
{
    $scanner = PhpstanTestCase::getContainer()
        ->getByType(FilamentProjectScannerFactory::class)
        ->create(
            filamentPaths: [],
            analysedPaths: [dirname(__DIR__, 2).'/Fixtures'],
        );

    $discover = new ReflectionMethod($scanner, 'discoverFilamentFiles');
    $indexMethod = new ReflectionMethod($scanner, 'indexFileMetadata');
    $rootsMethod = new ReflectionMethod($scanner, 'findResourceRoots');

    $filePaths = $discover->invoke($scanner);
    $index = $indexMethod->invoke($scanner, $filePaths);
    $roots = $rootsMethod->invoke($scanner, $index);

    return new ProjectScanResult(index: $index, roots: $roots);
}

it('resolves model from static property on PostResource', function () {
    $result = getBaseResultForModels();
    $enriched = getModelTransformer()->transform($result);
    $models = $enriched->get(ResourceModels::class);

    expect($models)->not->toBeNull()
        ->and($models->has('Fixtures\App\Resources\PostResource'))->toBeTrue()
        ->and($models->get('Fixtures\App\Resources\PostResource'))
        ->toBe('Fixtures\App\Models\Post');
});

it('resolves model from static property on CommentResource', function () {
    $result = getBaseResultForModels();
    $enriched = getModelTransformer()->transform($result);
    $models = $enriched->get(ResourceModels::class);

    expect($models)->not->toBeNull()
        ->and($models->has('Fixtures\App\Resources\CommentResource'))->toBeTrue()
        ->and($models->get('Fixtures\App\Resources\CommentResource'))
        ->toBe('Fixtures\App\Models\Comment');
});

it('resolves model from literal return in getModel via AST', function () {
    $result = getBaseResultForModels();
    $enriched = getModelTransformer()->transform($result);
    $models = $enriched->get(ResourceModels::class);

    // ResourceWithLiteralGetModel declares getModel() returning TestModel::class
    expect($models->has('ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\ResourceWithLiteralGetModel'))->toBeTrue()
        ->and($models->get('ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\ResourceWithLiteralGetModel'))
        ->toBe('ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\TestModel');
});

it('resolves model from phpdoc class-string annotation via PHPStan reflection', function () {
    $result = getBaseResultForModels();
    $enriched = getModelTransformer()->transform($result);
    $models = $enriched->get(ResourceModels::class);

    // ResourceWithPhpDocGetModel has @return class-string<TestModel>
    expect($models->has('ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\ResourceWithPhpDocGetModel'))->toBeTrue()
        ->and($models->get('ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\ResourceWithPhpDocGetModel'))
        ->toBe('ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\TestModel');
});

it('returns empty resourceModels for empty result', function () {
    $result = new ProjectScanResult(index: [], roots: []);
    $enriched = getModelTransformer()->transform($result);
    $models = $enriched->get(ResourceModels::class);

    expect($models)->not->toBeNull()
        ->and($models->all())->toBe([]);
});

it('does not resolve model from property when getModel is declared (ResourceWithLiteralGetModelAndProperty)', function () {
    $result = getBaseResultForModels();
    $enriched = getModelTransformer()->transform($result);
    $models = $enriched->get(ResourceModels::class);

    // ResourceWithLiteralGetModelAndProperty has both $model and getModel() returning self::$model
    // AST path returns null for self::$model, PHPStan reflection returns string (no class-string<T>),
    // and property fallback is skipped because getModel is declared — so no model resolved
    $fqcn = 'ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\ResourceWithLiteralGetModelAndProperty';
    expect($models->has($fqcn))->toBeFalse();
});
