<?php

// tests/Unit/ComponentContextResolverTest.php

use ImSuperlative\PhpstanFilament\Data\FilamentContext;
use ImSuperlative\PhpstanFilament\Parser\TypeStringParser;
use ImSuperlative\PhpstanFilament\Resolvers\AnnotationReader;
use ImSuperlative\PhpstanFilament\Resolvers\AttributeAnnotationParser;
use ImSuperlative\PhpstanFilament\Resolvers\ComponentContextResolver;
use ImSuperlative\PhpstanFilament\Resolvers\PhpDocAnnotationParser;
use ImSuperlative\PhpstanFilament\Resolvers\ResourceModelResolver;
use ImSuperlative\PhpstanFilament\Resolvers\VirtualAnnotationProvider;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use ImSuperlative\PhpstanFilament\Support\ModelReflectionHelper;
use ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\TestEditPage;
use ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\TestModel;
use ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\TestResource;
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
