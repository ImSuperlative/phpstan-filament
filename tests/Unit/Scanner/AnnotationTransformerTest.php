<?php

use ImSuperlative\PhpstanFilament\Data\FilamentTagAnnotation;
use ImSuperlative\PhpstanFilament\Data\FileMetadata;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentAnnotations;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Data\Scanner\ExplicitAnnotations;
use ImSuperlative\PhpstanFilament\Resolvers\AnnotationParser;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\Enrichment\AnnotationTransformer;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;

it('reads annotations and stores resolved FQCNs', function () {
    $modelAnnotation = new FilamentTagAnnotation(type: new IdentifierTypeNode('App\Models\Post'));
    $pageAnnotation = new FilamentTagAnnotation(type: new IdentifierTypeNode('App\Pages\EditPost'));

    $classReflection = new ReflectionClass(ClassReflection::class)->newInstanceWithoutConstructor();

    $reflectionProvider = Mockery::mock(ReflectionProvider::class);
    $reflectionProvider->shouldReceive('hasClass')->with('App\PostForm')->andReturn(true);
    $reflectionProvider->shouldReceive('hasClass')->with('App\Models\Post')->andReturn(true);
    $reflectionProvider->shouldReceive('hasClass')->with('App\Pages\EditPost')->andReturn(true);
    $reflectionProvider->shouldReceive('getClass')->with('App\PostForm')->andReturn($classReflection);

    $annotationReader = Mockery::mock(AnnotationParser::class);
    $annotationReader->shouldReceive('readModelAnnotation')->andReturn($modelAnnotation->toModelAnnotation());
    $annotationReader->shouldReceive('readPageAnnotations')->andReturn([$pageAnnotation->toPageAnnotation()]);
    $annotationReader->shouldReceive('readStateAnnotations')->andReturn([]);
    $annotationReader->shouldReceive('readFieldAnnotations')->andReturn([]);

    $result = new ProjectScanResult(index: [], roots: []);
    $result->set(new ComponentToResources([
        'App\PostForm' => ['App\PostResource'],
    ]));

    $transformer = new AnnotationTransformer($reflectionProvider, $annotationReader);
    $enriched = $transformer->transform($result);

    expect($enriched->has(ComponentAnnotations::class))->toBeTrue();

    $explicit = $enriched->get(ComponentAnnotations::class)->get('App\PostForm');

    expect($explicit)->toBeInstanceOf(ExplicitAnnotations::class)
        ->and($explicit->model)->toBe('App\Models\Post')
        ->and($explicit->pageModels)->toBe(['App\Pages\EditPost' => null]);
});

it('resolves short names to FQCNs using FileMetadata useMap', function () {
    $modelAnnotation = new FilamentTagAnnotation(type: new IdentifierTypeNode('Post'));
    $pageAnnotation = new FilamentTagAnnotation(type: new IdentifierTypeNode('EditPost'));

    $classReflection = new ReflectionClass(ClassReflection::class)->newInstanceWithoutConstructor();

    $reflectionProvider = Mockery::mock(ReflectionProvider::class);
    $reflectionProvider->shouldReceive('hasClass')->with('App\Schemas\PostForm')->andReturn(true);
    $reflectionProvider->shouldReceive('hasClass')->with('Post')->andReturn(false);
    $reflectionProvider->shouldReceive('hasClass')->with('EditPost')->andReturn(false);
    $reflectionProvider->shouldReceive('getClass')->with('App\Schemas\PostForm')->andReturn($classReflection);

    $annotationReader = Mockery::mock(AnnotationParser::class);
    $annotationReader->shouldReceive('readModelAnnotation')->andReturn($modelAnnotation->toModelAnnotation());
    $annotationReader->shouldReceive('readPageAnnotations')->andReturn([$pageAnnotation->toPageAnnotation()]);
    $annotationReader->shouldReceive('readStateAnnotations')->andReturn([]);
    $annotationReader->shouldReceive('readFieldAnnotations')->andReturn([]);

    $result = new ProjectScanResult(
        index: [
            'app/Schemas/PostForm.php' => new FileMetadata(
                fullyQualifiedName: 'App\Schemas\PostForm',
                extends: null,
                traits: [],
                useMap: [
                    'Post' => 'App\Models\Post',
                    'EditPost' => 'App\Pages\EditPost',
                ],
                namespace: 'App\Schemas',
                isTrait: false,
            ),
        ],
        roots: [],
    );
    $result->set(new ComponentToResources([
        'App\Schemas\PostForm' => ['App\PostResource'],
    ]));

    $transformer = new AnnotationTransformer($reflectionProvider, $annotationReader);
    $enriched = $transformer->transform($result);

    $explicit = $enriched->get(ComponentAnnotations::class)->get('App\Schemas\PostForm');

    expect($explicit->model)->toBe('App\Models\Post')
        ->and($explicit->pageModels)->toBe(['App\Pages\EditPost' => null]);
});

it('skips components not found in reflection', function () {
    $reflectionProvider = Mockery::mock(ReflectionProvider::class);
    $reflectionProvider->shouldReceive('hasClass')->with('App\PostForm')->andReturn(false);

    $annotationReader = Mockery::mock(AnnotationParser::class);

    $result = new ProjectScanResult(index: [], roots: []);
    $result->set(new ComponentToResources([
        'App\PostForm' => ['App\PostResource'],
    ]));

    $transformer = new AnnotationTransformer($reflectionProvider, $annotationReader);
    $enriched = $transformer->transform($result);

    expect($enriched->get(ComponentAnnotations::class)->get('App\PostForm'))->toBeNull();
});

it('skips components with no annotations', function () {
    $classReflection = new ReflectionClass(ClassReflection::class)->newInstanceWithoutConstructor();

    $reflectionProvider = Mockery::mock(ReflectionProvider::class);
    $reflectionProvider->shouldReceive('hasClass')->with('App\PostForm')->andReturn(true);
    $reflectionProvider->shouldReceive('getClass')->with('App\PostForm')->andReturn($classReflection);

    $annotationReader = Mockery::mock(AnnotationParser::class);
    $annotationReader->shouldReceive('readModelAnnotation')->andReturn(null);
    $annotationReader->shouldReceive('readPageAnnotations')->andReturn([]);
    $annotationReader->shouldReceive('readStateAnnotations')->andReturn([]);
    $annotationReader->shouldReceive('readFieldAnnotations')->andReturn([]);

    $result = new ProjectScanResult(index: [], roots: []);
    $result->set(new ComponentToResources([
        'App\PostForm' => ['App\PostResource'],
    ]));

    $transformer = new AnnotationTransformer($reflectionProvider, $annotationReader);
    $enriched = $transformer->transform($result);

    expect($enriched->get(ComponentAnnotations::class)->get('App\PostForm'))->toBeNull();
});

it('returns empty ComponentAnnotations when no ComponentToResources', function () {
    $reflectionProvider = Mockery::mock(ReflectionProvider::class);
    $annotationReader = Mockery::mock(AnnotationParser::class);

    $result = new ProjectScanResult(index: [], roots: []);

    $transformer = new AnnotationTransformer($reflectionProvider, $annotationReader);
    $enriched = $transformer->transform($result);

    expect($enriched->has(ComponentAnnotations::class))->toBeTrue()
        ->and($enriched->get(ComponentAnnotations::class)->all())->toBe([]);
});
