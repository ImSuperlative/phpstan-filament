<?php

use ImSuperlative\PhpstanFilament\Data\FilamentContext;
use ImSuperlative\PhpstanFilament\Resolvers\ComponentContextResolver;
use ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\TestEditPage;
use ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\TestModel;
use ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\TestResource;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;

function getResolver(): ComponentContextResolver
{
    return PhpstanTestCase::getContainer()->getByType(ComponentContextResolver::class);
}

it('resolves model from resource page class name', function () {
    expect(getResolver()->fromClassName(TestEditPage::class))
        ->toBeInstanceOf(FilamentContext::class)
        ->resourceClass->toBe(TestResource::class)
        ->modelClass->toBe(TestModel::class);
});

it('resolves model from resource class name', function () {
    expect(getResolver()->fromClassName(TestResource::class))
        ->modelClass->toBe(TestModel::class)
        ->resourceClass->toBe(TestResource::class);
});

it('returns empty context for unknown class', function () {
    expect(getResolver()->fromClassName('App\Unknown\Class'))
        ->modelClass->toBeNull()
        ->resourceClass->toBeNull()
        ->componentClass->toBeNull();
});

it('resolves from annotation', function () {
    expect(getResolver()->fromAnnotation('App\Models\Form'))
        ->modelClass->toBe('App\Models\Form')
        ->resourceClass->toBeNull();
});
