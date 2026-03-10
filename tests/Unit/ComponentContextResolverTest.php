<?php

// tests/Unit/ComponentContextResolverTest.php

use ImSuperlative\FilamentPhpstan\Data\FilamentContext;
use ImSuperlative\FilamentPhpstan\Parser\TypeStringParser;
use ImSuperlative\FilamentPhpstan\Resolvers\AnnotationReader;
use ImSuperlative\FilamentPhpstan\Resolvers\AttributeAnnotationParser;
use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\PhpDocAnnotationParser;
use ImSuperlative\FilamentPhpstan\Resolvers\ResourceModelResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\VirtualAnnotationProvider;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use ImSuperlative\FilamentPhpstan\Support\ModelReflectionHelper;
use ImSuperlative\FilamentPhpstan\Tests\Fixtures\Stubs\TestEditPage;
use ImSuperlative\FilamentPhpstan\Tests\Fixtures\Stubs\TestModel;
use ImSuperlative\FilamentPhpstan\Tests\Fixtures\Stubs\TestResource;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

beforeEach(function () {
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $filamentClassHelper = new FilamentClassHelper($reflectionProvider);

    $typeStringParser = TypeStringParser::make();

    $modelReflectionHelper = new ModelReflectionHelper($reflectionProvider);
    $resourceModelResolver = new ResourceModelResolver($reflectionProvider, $filamentClassHelper, $modelReflectionHelper);

    $annotationReader = new AnnotationReader(
        new AttributeAnnotationParser($typeStringParser),
        new PhpDocAnnotationParser($typeStringParser),
    );

    $this->resolver = new ComponentContextResolver(
        $filamentClassHelper,
        $resourceModelResolver,
        $annotationReader,
        $reflectionProvider,
        new ModelReflectionHelper($reflectionProvider),
        new VirtualAnnotationProvider(
            enabled: false,
            filamentPath: [],
            currentWorkingDirectory: '',
            analysedPaths: [],
            analysedPathsFromConfig: [],
            resourceModelResolver: $resourceModelResolver,
        ),
    );
});

it('resolves model from resource page class name', function () {
    $context = $this->resolver->fromClassName(TestEditPage::class);

    expect($context)
        ->toBeInstanceOf(FilamentContext::class)
        ->resourceClass->toBe(TestResource::class)
        ->modelClass->toBe(TestModel::class);
});

it('resolves model from resource class name', function () {
    $context = $this->resolver->fromClassName(TestResource::class);

    expect($context)
        ->modelClass->toBe(TestModel::class)
        ->resourceClass->toBe(TestResource::class);
});

it('returns empty context for unknown class', function () {
    $context = $this->resolver->fromClassName('App\Unknown\Class');

    expect($context)
        ->modelClass->toBeNull()
        ->resourceClass->toBeNull()
        ->componentClass->toBeNull();
});

it('resolves from annotation', function () {
    $context = $this->resolver->fromAnnotation('App\Models\Form');

    expect($context)
        ->modelClass->toBe('App\Models\Form')
        ->resourceClass->toBeNull();
});
