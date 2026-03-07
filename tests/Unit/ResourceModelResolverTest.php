<?php

// tests/Unit/ResourceModelResolverTest.php

use Fixtures\App\Models\Post;
use Fixtures\App\Resources\PostResource\RelationManagers\CommentsRelationManager;
use ImSuperlative\FilamentPhpstan\Resolvers\ResourceModelResolver;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use ImSuperlative\FilamentPhpstan\Tests\Fixtures\Stubs\TestEditPage;
use ImSuperlative\FilamentPhpstan\Tests\Fixtures\Stubs\TestModel;
use ImSuperlative\FilamentPhpstan\Tests\Fixtures\Stubs\TestResource;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

beforeEach(function () {
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $this->resolver = new ResourceModelResolver(
        $reflectionProvider,
        new FilamentClassHelper($reflectionProvider),
    );
});

it('resolves model from resource class', function () {
    expect($this->resolver->resolve(TestResource::class))
        ->toBe(TestModel::class);
});

it('resolves model from resource page class', function () {
    expect($this->resolver->resolve(TestEditPage::class))
        ->toBe(TestModel::class);
});

it('returns null for non-resource class', function () {
    expect($this->resolver->resolve('App\Models\User'))
        ->toBeNull();
});

it('returns null for class without model property', function () {
    expect($this->resolver->resolve('stdClass'))
        ->toBeNull();
});

it('resolves model from relation manager via namespace convention', function () {
    expect($this->resolver->resolve(
        CommentsRelationManager::class
    ))->toBe(Post::class);
});

it('caches results', function () {
    $first = $this->resolver->resolve(TestResource::class);
    $second = $this->resolver->resolve(TestResource::class);

    expect($first)->toBe($second)->toBe(TestModel::class);
});
