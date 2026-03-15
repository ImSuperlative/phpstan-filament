<?php

use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceModels;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourcePages;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceRelations;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;

it('constructs with core fields', function () {
    $result = new ProjectScanResult(
        index: ['file.php' => 'metadata'],
        roots: ['file.php'],
    );

    expect($result->index)->toBe(['file.php' => 'metadata'])
        ->and($result->roots)->toBe(['file.php']);
});

it('stores and retrieves attributes via set/get/has', function () {
    $result = new ProjectScanResult(index: [], roots: []);
    $data = new stdClass;
    $data->value = 'test';

    expect($result->has(stdClass::class))->toBeFalse()
        ->and($result->find(stdClass::class))->toBeNull();

    $same = $result->set($data);

    expect($same)->toBe($result)
        ->and($result->has(stdClass::class))->toBeTrue()
        ->and($result->get(stdClass::class))->toBe($data);
});

it('stores scanner data classes in attribute bag', function () {
    $result = new ProjectScanResult(index: [], roots: []);

    $pages = new ResourcePages(['App\Resource' => ['index' => 'App\Pages\ListPosts']]);
    $relations = new ResourceRelations(['App\Resource' => ['App\Relations\CommentsRelationManager']]);
    $models = new ResourceModels(['App\Resource' => 'App\Models\Post']);
    $components = new ComponentToResources(['App\PostForm' => ['App\PostResource']]);

    $result->set($pages);
    $result->set($relations);
    $result->set($models);
    $result->set($components);

    expect($result->get(ResourcePages::class))->toBe($pages)
        ->and($result->get(ResourceRelations::class))->toBe($relations)
        ->and($result->get(ResourceModels::class))->toBe($models)
        ->and($result->get(ComponentToResources::class))->toBe($components);
});

it('returns null for unset attributes', function () {
    $result = new ProjectScanResult(index: [], roots: []);

    expect($result->find(ResourcePages::class))->toBeNull()
        ->and($result->has(ResourcePages::class))->toBeFalse();
});
