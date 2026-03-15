<?php

use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentAnnotations;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentContext;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentDeclaration;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentDeclarations;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentNode;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Data\Scanner\ExplicitAnnotations;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceModels;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourcePages;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\Enrichment\ComponentContextTransformer;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;
use PHPStan\Reflection\ReflectionProvider;

function getComponentContextTransformer(): ComponentContextTransformer
{
    $container = PhpstanTestCase::getContainer();

    return new ComponentContextTransformer(
        $container->getByType(ReflectionProvider::class),
    );
}

function buildScanResult(
    array $componentToResources = [],
    array $resourceModels = [],
    array $resourcePages = [],
    array $componentAnnotations = [],
): ProjectScanResult {
    $result = new ProjectScanResult(index: [], roots: []);
    $result->set(new ComponentToResources($componentToResources));
    $result->set(new ResourceModels($resourceModels));
    $result->set(new ResourcePages($resourcePages));
    $result->set(new ComponentAnnotations($componentAnnotations));

    return $result;
}

it('builds context from graph-inferred data', function () {
    $result = buildScanResult(
        componentToResources: ['App\PostForm' => ['App\PostResource']],
        resourceModels: ['App\PostResource' => 'App\Models\Post'],
        resourcePages: ['App\PostResource' => ['edit' => 'App\Pages\EditPost']],
    );

    $transformer = getComponentContextTransformer();
    $enriched = $transformer->transform($result);

    $node = $enriched->get(ComponentContext::class)->get('App\PostForm');

    expect($node)->toBeInstanceOf(ComponentNode::class)
        ->and($node->explicitModel)->toBeNull()
        ->and($node->resourceModels)->toBe(['App\PostResource' => 'App\Models\Post'])
        ->and($node->resourcePages)->toBe(['App\PostResource' => ['App\Pages\EditPost']])
        ->and($node->pageModels)->toHaveKey('App\Pages\EditPost', 'App\Models\Post')
        ->and($node->pageModels)->toHaveKey('App\PostResource', 'App\Models\Post');
});

it('explicit model annotation is stored as explicitModel', function () {
    $result = buildScanResult(
        componentToResources: ['App\PostForm' => ['App\PostResource']],
        resourceModels: ['App\PostResource' => 'App\Models\Post'],
        componentAnnotations: [
            'App\PostForm' => new ExplicitAnnotations(
                model: 'App\Models\Comment',
            ),
        ],
    );

    $transformer = getComponentContextTransformer();
    $enriched = $transformer->transform($result);

    $node = $enriched->get(ComponentContext::class)->get('App\PostForm');

    expect($node->explicitModel)->toBe('App\Models\Comment');
});

it('explicit page annotations build pageModels with model', function () {
    $result = buildScanResult(
        componentToResources: ['App\PostForm' => ['App\PostResource']],
        resourceModels: ['App\PostResource' => 'App\Models\Post'],
        componentAnnotations: [
            'App\PostForm' => new ExplicitAnnotations(
                pageModels: ['App\Pages\EditPost' => 'App\Models\Comment'],
            ),
        ],
    );

    $transformer = getComponentContextTransformer();
    $enriched = $transformer->transform($result);

    $node = $enriched->get(ComponentContext::class)->get('App\PostForm');

    expect($node->pageModels)->toBe(['App\Pages\EditPost' => 'App\Models\Comment']);
});

it('explicit page annotations without model have null model', function () {
    $result = buildScanResult(
        componentToResources: ['App\PostForm' => ['App\PostResource']],
        componentAnnotations: [
            'App\PostForm' => new ExplicitAnnotations(
                pageModels: ['App\Pages\EditPost' => null],
            ),
        ],
    );

    $transformer = getComponentContextTransformer();
    $enriched = $transformer->transform($result);

    $node = $enriched->get(ComponentContext::class)->get('App\PostForm');

    expect($node->pageModels)->toBe(['App\Pages\EditPost' => null]);
});

it('handles component with multiple resources', function () {
    $result = buildScanResult(
        componentToResources: ['App\SharedForm' => ['App\PostResource', 'App\UserResource']],
        resourceModels: [
            'App\PostResource' => 'App\Models\Post',
            'App\UserResource' => 'App\Models\User',
        ],
        resourcePages: [
            'App\PostResource' => ['edit' => 'App\Pages\EditPost'],
            'App\UserResource' => ['edit' => 'App\Pages\EditUser'],
        ],
    );

    $transformer = getComponentContextTransformer();
    $enriched = $transformer->transform($result);

    $node = $enriched->get(ComponentContext::class)->get('App\SharedForm');

    expect($node->resourceModels)->toBe([
        'App\PostResource' => 'App\Models\Post',
        'App\UserResource' => 'App\Models\User',
    ])
        ->and($node->pageModels)->toHaveKey('App\Pages\EditPost', 'App\Models\Post')
        ->and($node->pageModels)->toHaveKey('App\Pages\EditUser', 'App\Models\User');
});

it('handles component with no model', function () {
    $result = buildScanResult(
        componentToResources: ['App\PostForm' => ['App\PostResource']],
        resourceModels: [],
    );

    $transformer = getComponentContextTransformer();
    $enriched = $transformer->transform($result);

    $node = $enriched->get(ComponentContext::class)->get('App\PostForm');

    expect($node->explicitModel)->toBeNull()
        ->and($node->resourceModels)->toBe(['App\PostResource' => null]);
});

it('returns empty context when no ComponentToResources', function () {
    $result = new ProjectScanResult(index: [], roots: []);
    $result->set(new ComponentAnnotations([]));

    $transformer = getComponentContextTransformer();
    $enriched = $transformer->transform($result);

    expect($enriched->has(ComponentContext::class))->toBeTrue();
    expect($enriched->get(ComponentContext::class)->all())->toBe([]);
});

it('populates owningResources from ComponentToResources', function () {
    $result = buildScanResult(
        componentToResources: ['App\PostForm' => ['App\PostResource', 'App\UserResource']],
        resourceModels: ['App\PostResource' => 'App\Models\Post'],
    );

    $transformer = getComponentContextTransformer();
    $enriched = $transformer->transform($result);

    $node = $enriched->get(ComponentContext::class)->get('App\PostForm');

    expect($node->owningResources)->toBe(['App\PostResource', 'App\UserResource']);
});

it('populates declaration fields from ComponentDeclarations', function () {
    $result = buildScanResult(
        componentToResources: ['App\CommentsRM' => ['App\PostResource']],
        resourceModels: ['App\PostResource' => 'App\Models\Post'],
    );
    $result->set(new ComponentDeclarations([
        'App\CommentsRM' => new ComponentDeclaration(
            resourceClass: 'App\PostResource',
            relatedResourceClass: null,
            relationshipName: 'comments',
        ),
    ]));

    $transformer = getComponentContextTransformer();
    $enriched = $transformer->transform($result);

    $node = $enriched->get(ComponentContext::class)->get('App\CommentsRM');

    expect($node->declaration->resourceClass)->toBe('App\PostResource')
        ->and($node->declaration->relatedResourceClass)->toBeNull()
        ->and($node->declaration->relationshipName)->toBe('comments');
});

it('handles missing ComponentDeclarations gracefully', function () {
    $result = buildScanResult(
        componentToResources: ['App\PostForm' => ['App\PostResource']],
        resourceModels: ['App\PostResource' => 'App\Models\Post'],
    );

    $transformer = getComponentContextTransformer();
    $enriched = $transformer->transform($result);

    $node = $enriched->get(ComponentContext::class)->get('App\PostForm');

    expect($node->owningResources)->toBe(['App\PostResource'])
        ->and($node->declaration->resourceClass)->toBeNull()
        ->and($node->declaration->relationshipName)->toBeNull();
});
