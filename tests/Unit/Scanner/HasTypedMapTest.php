<?php

use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceModels;
use ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\TestValueProvider;

it('wraps a single value via into()', function () {
    $map = new ResourceModels([
        'App\PostResource' => 'App\Models\Post',
    ]);

    $provider = $map->into('App\PostResource', TestValueProvider::class);

    expect($provider)->toBeInstanceOf(TestValueProvider::class)
        ->and($provider->value)->toBe('App\Models\Post');
});

it('returns null from into() when key does not exist', function () {
    $map = new ResourceModels([]);

    expect($map->into('App\Missing', TestValueProvider::class))->toBeNull();
});

it('wraps all values via mapInto()', function () {
    $map = new ResourceModels([
        'App\PostResource' => 'App\Models\Post',
        'App\UserResource' => 'App\Models\User',
    ]);

    $providers = $map->mapInto(TestValueProvider::class);

    expect($providers)->toHaveCount(2)
        ->and($providers['App\PostResource'])->toBeInstanceOf(TestValueProvider::class)
        ->and($providers['App\PostResource']->value)->toBe('App\Models\Post')
        ->and($providers['App\UserResource']->value)->toBe('App\Models\User');
});
