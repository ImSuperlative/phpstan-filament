<?php

use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentContext;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentDeclaration;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentNode;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentTag;
use ImSuperlative\PhpstanFilament\Resolvers\ComponentProvider;

it('returns single model when unambiguous', function () {
    $node = new ComponentNode(pageModels: ['App\Pages\EditPost' => 'App\Models\Post']);
    $provider = new ComponentProvider($node);

    expect($provider->getModel())->toBe('App\Models\Post');
});

it('returns explicit model over page models', function () {
    $node = new ComponentNode(
        explicitModel: 'App\Models\Comment',
        pageModels: ['App\Pages\EditPost' => 'App\Models\Post'],
    );
    $provider = new ComponentProvider($node);

    expect($provider->getModel())->toBe('App\Models\Comment');
});

it('returns null model when ambiguous', function () {
    $node = new ComponentNode(pageModels: [
        'App\Pages\EditPost' => 'App\Models\Post',
        'App\Pages\EditUser' => 'App\Models\User',
    ]);
    $provider = new ComponentProvider($node);

    expect($provider->getModel())->toBeNull();
});

it('returns all unique model classes', function () {
    $node = new ComponentNode(pageModels: [
        'App\Pages\EditPost' => 'App\Models\Post',
        'App\PostResource' => 'App\Models\Post',
        'App\Pages\EditUser' => 'App\Models\User',
    ]);
    $provider = new ComponentProvider($node);

    expect($provider->getModelClasses())->toBe(['App\Models\Post', 'App\Models\User']);
});

it('returns model for a specific page', function () {
    $node = new ComponentNode(pageModels: [
        'App\Pages\EditPost' => 'App\Models\Post',
        'App\Pages\EditUser' => 'App\Models\User',
    ]);
    $provider = new ComponentProvider($node);

    expect($provider->getModelForPage('App\Pages\EditPost'))->toBe('App\Models\Post')
        ->and($provider->getModelForPage('App\Unknown'))->toBeNull();
});

it('returns model for a specific resource', function () {
    $node = new ComponentNode(resourceModels: [
        'App\PostResource' => 'App\Models\Post',
    ]);
    $provider = new ComponentProvider($node);

    expect($provider->getModelForResource('App\PostResource'))->toBe('App\Models\Post')
        ->and($provider->getModelForResource('App\Unknown'))->toBeNull();
});

it('returns pages for a specific resource', function () {
    $node = new ComponentNode(resourcePages: [
        'App\PostResource' => ['App\Pages\EditPost', 'App\Pages\CreatePost'],
    ]);
    $provider = new ComponentProvider($node);

    expect($provider->getPagesForResource('App\PostResource'))->toBe(['App\Pages\EditPost', 'App\Pages\CreatePost'])
        ->and($provider->getPagesForResource('App\Unknown'))->toBe([]);
});

it('returns all pages from pageModels keys', function () {
    $node = new ComponentNode(pageModels: [
        'App\Pages\EditPost' => 'App\Models\Post',
        'App\PostResource' => 'App\Models\Post',
    ]);
    $provider = new ComponentProvider($node);

    expect($provider->getPages())->toBe(['App\Pages\EditPost', 'App\PostResource']);
});

it('returns all resources from resourceModels keys', function () {
    $node = new ComponentNode(resourceModels: [
        'App\PostResource' => 'App\Models\Post',
        'App\UserResource' => 'App\Models\User',
    ]);
    $provider = new ComponentProvider($node);

    expect($provider->getResources())->toBe(['App\PostResource', 'App\UserResource']);
});

it('can be created via HasTypedMap::into()', function () {
    $context = new ComponentContext([
        'App\PostForm' => new ComponentNode(
            pageModels: ['App\Pages\EditPost' => 'App\Models\Post'],
        ),
    ]);

    $provider = $context->into('App\PostForm', ComponentProvider::class);

    expect($provider)->toBeInstanceOf(ComponentProvider::class)
        ->and($provider->getModel())->toBe('App\Models\Post');
});

it('returns null from into() for unknown component', function () {
    $context = new ComponentContext([]);

    expect($context->into('App\Unknown', ComponentProvider::class))->toBeNull();
});

it('returns null model when no data', function () {
    $node = new ComponentNode;
    $provider = new ComponentProvider($node);

    expect($provider->getModel())->toBeNull()
        ->and($provider->getModelClasses())->toBe([]);
});

it('returns owning resources', function () {
    $node = new ComponentNode(owningResources: ['App\PostResource', 'App\UserResource']);
    $provider = new ComponentProvider($node);

    expect($provider->getOwningResources())->toBe(['App\PostResource', 'App\UserResource']);
});

it('returns empty owning resources when none', function () {
    $node = new ComponentNode;
    $provider = new ComponentProvider($node);

    expect($provider->getOwningResources())->toBe([]);
});

it('returns resource class', function () {
    $node = new ComponentNode(declaration: new ComponentDeclaration(resourceClass: 'App\PostResource'));
    $provider = new ComponentProvider($node);

    expect($provider->getResourceClass())->toBe('App\PostResource');
});

it('returns related resource class', function () {
    $node = new ComponentNode(declaration: new ComponentDeclaration(relatedResourceClass: 'App\CommentResource'));
    $provider = new ComponentProvider($node);

    expect($provider->getRelatedResourceClass())->toBe('App\CommentResource');
});

it('returns relationship name', function () {
    $node = new ComponentNode(declaration: new ComponentDeclaration(relationshipName: 'comments'));
    $provider = new ComponentProvider($node);

    expect($provider->getRelationshipName())->toBe('comments');
});

it('returns null for unset declaration fields', function () {
    $node = new ComponentNode;
    $provider = new ComponentProvider($node);

    expect($provider->getResourceClass())->toBeNull()
        ->and($provider->getRelatedResourceClass())->toBeNull()
        ->and($provider->getRelationshipName())->toBeNull();
});

it('hasTag returns true when tag present', function () {
    $provider = new ComponentProvider(new ComponentNode(tags: [ComponentTag::Page, ComponentTag::EditPage]));
    expect($provider->hasTag(ComponentTag::Page))->toBeTrue()
        ->and($provider->hasTag(ComponentTag::Resource))->toBeFalse();
});

it('hasAnyTag returns true when any tag matches', function () {
    $provider = new ComponentProvider(new ComponentNode(tags: [ComponentTag::Page, ComponentTag::EditPage]));
    expect($provider->hasAnyTag([ComponentTag::Resource, ComponentTag::Page]))->toBeTrue()
        ->and($provider->hasAnyTag([ComponentTag::Resource, ComponentTag::RelationManager]))->toBeFalse();
});

it('isNested detects nested tag', function () {
    $provider = new ComponentProvider(new ComponentNode(tags: [ComponentTag::Page, ComponentTag::Nested]));
    expect($provider->isNested())->toBeTrue();
});

it('isNested detects ManageRelatedRecords tag', function () {
    $provider = new ComponentProvider(new ComponentNode(tags: [ComponentTag::Page, ComponentTag::ManageRelatedRecords]));
    expect($provider->isNested())->toBeTrue();
});

it('isNested returns false for regular page', function () {
    $provider = new ComponentProvider(new ComponentNode(tags: [ComponentTag::Page, ComponentTag::EditPage]));
    expect($provider->isNested())->toBeFalse();
});

it('getOwnerModel resolves from declaration resourceClass', function () {
    $provider = new ComponentProvider(new ComponentNode(
        resourceModels: ['App\\Resources\\PostResource' => 'App\\Models\\Post'],
        declaration: new ComponentDeclaration(resourceClass: 'App\\Resources\\PostResource'),
    ));
    expect($provider->getOwnerModel())->toBe('App\\Models\\Post');
});

it('getOwnerModel falls back to owning resources', function () {
    $provider = new ComponentProvider(new ComponentNode(
        resourceModels: ['App\\Resources\\PostResource' => 'App\\Models\\Post'],
        owningResources: ['App\\Resources\\PostResource'],
    ));
    expect($provider->getOwnerModel())->toBe('App\\Models\\Post');
});

it('getOwnerModel returns null when no resource context', function () {
    $provider = new ComponentProvider(new ComponentNode);
    expect($provider->getOwnerModel())->toBeNull();
});
