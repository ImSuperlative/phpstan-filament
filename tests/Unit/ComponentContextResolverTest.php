<?php

// tests/Unit/ComponentContextResolverTest.php

use ImSuperlative\FilamentPhpstan\Collectors\CustomComponentRegistry;
use ImSuperlative\FilamentPhpstan\Collectors\SchemaCallSiteRegistry;
use ImSuperlative\FilamentPhpstan\Collectors\TableQueryRegistry;
use ImSuperlative\FilamentPhpstan\Data\FilamentContext;
use ImSuperlative\FilamentPhpstan\Resolvers\AnnotationReader;
use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\ResourceModelResolver;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use ImSuperlative\FilamentPhpstan\Support\ModelReflectionHelper;
use ImSuperlative\FilamentPhpstan\Tests\Fixtures\Stubs\TestEditPage;
use ImSuperlative\FilamentPhpstan\Tests\Fixtures\Stubs\TestModel;
use ImSuperlative\FilamentPhpstan\Tests\Fixtures\Stubs\TestResource;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

beforeEach(function () {
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $filamentClassHelper = new FilamentClassHelper($reflectionProvider);

    $config = new ParserConfig(usedAttributes: []);
    $lexer = new Lexer($config);
    $constExprParser = new ConstExprParser($config);
    $typeParser = new TypeParser($config, $constExprParser);
    $phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);

    $this->resolver = new ComponentContextResolver(
        $filamentClassHelper,
        new ResourceModelResolver($reflectionProvider, $filamentClassHelper),
        new AnnotationReader($lexer, $typeParser, $phpDocParser),
        new TableQueryRegistry,
        $reflectionProvider,
        new ModelReflectionHelper($reflectionProvider),
        new CustomComponentRegistry,
        new SchemaCallSiteRegistry,
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
