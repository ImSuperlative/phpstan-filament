<?php

/** @noinspection ClassConstantCanBeUsedInspection */

use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceModels;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourcePages;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceRelations;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\Graph\ModelTransformer;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Tests\Factories\FilamentProjectScannerFactory;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;
use PHPStan\Reflection\ReflectionProvider;

function getModelTransformer(): ModelTransformer
{
    $container = PhpstanTestCase::getContainer();

    return new ModelTransformer(
        $container->getByType(ReflectionProvider::class),
        $container->getByType(FileParser::class),
    );
}

function getBaseResultForModels(): ProjectScanResult
{
    return PhpstanTestCase::getContainer()
        ->getByType(FilamentProjectScannerFactory::class)
        ->create(
            filamentPaths: [],
            analysedPaths: [tests_path('Fixtures')],
        )
        ->index();
}

it('resolves model from static property on PostResource', function () {
    $result = getBaseResultForModels();
    $enriched = getModelTransformer()->transform($result);
    $models = $enriched->get(ResourceModels::class);

    expect($models)->not->toBeNull()
        ->and($models->has('Fixtures\App\Resources\Post\PostResource'))->toBeTrue()
        ->and($models->get('Fixtures\App\Resources\Post\PostResource'))
        ->toBe('Fixtures\App\Models\Post');
});

it('resolves model from static property on CommentResource', function () {
    $result = getBaseResultForModels();
    $enriched = getModelTransformer()->transform($result);
    $models = $enriched->get(ResourceModels::class);

    expect($models)->not->toBeNull()
        ->and($models->has('Fixtures\App\Resources\Comment\CommentResource'))->toBeTrue()
        ->and($models->get('Fixtures\App\Resources\Comment\CommentResource'))
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

it('resolves model for resource pages via ResourcePages', function () {
    $result = getBaseResultForModels();
    $result->set(new ResourcePages([
        'Fixtures\App\Resources\Post\PostResource' => [
            'index' => 'Fixtures\App\Resources\Post\Pages\ListPosts',
            'edit' => 'Fixtures\App\Resources\Post\Pages\EditPost',
        ],
    ]));
    $result->set(new ResourceRelations([]));

    $enriched = getModelTransformer()->transform($result);
    $models = $enriched->get(ResourceModels::class);

    expect($models->get('Fixtures\App\Resources\Post\Pages\ListPosts'))
        ->toBe('Fixtures\App\Models\Post')
        ->and($models->get('Fixtures\App\Resources\Post\Pages\EditPost'))
        ->toBe('Fixtures\App\Models\Post');
});

it('resolves related model for relation managers via ResourceRelations', function () {
    $result = getBaseResultForModels();
    $result->set(new ResourcePages([]));
    $result->set(new ResourceRelations([
        'Fixtures\App\Resources\Post\PostResource' => [
            'Fixtures\App\Resources\Post\RelationManagers\CommentsRelationManager',
        ],
    ]));

    $enriched = getModelTransformer()->transform($result);
    $models = $enriched->get(ResourceModels::class);

    expect($models->get('Fixtures\App\Resources\Post\RelationManagers\CommentsRelationManager'))
        ->toBe('Fixtures\App\Models\Comment');
});

it('falls back to parent model when relationship cannot be resolved', function () {
    $result = getBaseResultForModels();
    $result->set(new ResourcePages([]));
    $result->set(new ResourceRelations([
        'Fixtures\App\Resources\Post\PostResource' => [
            'Fixtures\App\Resources\Post\RelationManagers\MediaRelationManager',
        ],
    ]));

    $enriched = getModelTransformer()->transform($result);
    $models = $enriched->get(ResourceModels::class);

    expect($models->get('Fixtures\App\Resources\Post\RelationManagers\MediaRelationManager'))
        ->toBe('Fixtures\App\Models\Post');
});
