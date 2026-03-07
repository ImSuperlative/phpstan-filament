<?php

use ImSuperlative\FilamentPhpstan\Collectors\CustomComponentRegistry;

it('returns null for unregistered class', function () {
    $registry = new CustomComponentRegistry;
    expect($registry->getModelForClass('App\Helpers\Foo'))->toBeNull();
});

it('stores and retrieves model for a helper class', function () {
    $registry = new CustomComponentRegistry;
    $registry->register('App\Helpers\CreatedAtEntry', 'App\Models\Post');
    expect($registry->getModelForClass('App\Helpers\CreatedAtEntry'))->toBe('App\Models\Post');
});

it('returns null for unregistered class when others are registered', function () {
    $registry = new CustomComponentRegistry;
    $registry->register('App\Helpers\CreatedAtEntry', 'App\Models\Post');
    expect($registry->getModelForClass('App\Helpers\OtherEntry'))->toBeNull();
});
