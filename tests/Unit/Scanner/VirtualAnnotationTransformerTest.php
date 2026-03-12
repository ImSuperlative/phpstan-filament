<?php

use ImSuperlative\PhpstanFilament\Data\FilamentPageAnnotation;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceModels;
use ImSuperlative\PhpstanFilament\Data\Scanner\VirtualAnnotations;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\VirtualAnnotationTransformer;

it('builds annotations from componentToResources and models', function () {
    $result = new ProjectScanResult(index: [], roots: []);
    $result->set(new ComponentToResources([
        'App\PostForm' => ['App\PostResource'],
    ]));
    $result->set(new ResourceModels([
        'App\PostResource' => 'App\Models\Post',
    ]));

    $transformer = new VirtualAnnotationTransformer(enabled: true, warnOnVirtual: false);
    $enriched = $transformer->transform($result);

    expect($enriched->has(VirtualAnnotations::class))->toBeTrue();

    $virtualAnnotations = $enriched->get(VirtualAnnotations::class);
    expect($virtualAnnotations->has('App\PostForm'))->toBeTrue()
        ->and($virtualAnnotations->get('App\PostForm'))->toHaveCount(1)
        ->and($virtualAnnotations->get('App\PostForm')[0])->toBeInstanceOf(FilamentPageAnnotation::class);

    // Check that annotation has model info
    $annotation = $virtualAnnotations->get('App\PostForm')[0];
    expect($annotation->modelType())->not->toBeNull()
        ->and((string) $annotation->modelType())->toBe('App\Models\Post');
});

it('builds annotations without model when model is unknown', function () {
    $result = new ProjectScanResult(index: [], roots: []);
    $result->set(new ComponentToResources([
        'App\PostForm' => ['App\PostResource'],
    ]));
    $result->set(new ResourceModels([]));

    $transformer = new VirtualAnnotationTransformer(enabled: true, warnOnVirtual: false);
    $enriched = $transformer->transform($result);

    $virtualAnnotations = $enriched->get(VirtualAnnotations::class);
    $annotation = $virtualAnnotations->get('App\PostForm')[0];

    expect($annotation->modelType())->toBeNull();
});

it('skips when both enabled and warnOnVirtual are false', function () {
    $result = new ProjectScanResult(index: [], roots: []);
    $result->set(new ComponentToResources([
        'App\PostForm' => ['App\PostResource'],
    ]));

    $transformer = new VirtualAnnotationTransformer(enabled: false, warnOnVirtual: false);
    $enriched = $transformer->transform($result);

    expect($enriched->has(VirtualAnnotations::class))->toBeFalse();
});
