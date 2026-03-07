<?php

use ImSuperlative\FilamentPhpstan\Collectors\CustomComponentRegistry;
use ImSuperlative\FilamentPhpstan\Collectors\SchemaCallSiteRegistry;
use ImSuperlative\FilamentPhpstan\Collectors\TableQueryRegistry;
use ImSuperlative\FilamentPhpstan\Resolvers\AnnotationReader;
use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\ResourceModelResolver;
use ImSuperlative\FilamentPhpstan\Rules\RelationshipValidationRule;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use ImSuperlative\FilamentPhpstan\Support\ModelReflectionHelper;
use ImSuperlative\FilamentPhpstan\Tests\ConfigurableRuleTestCase;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

function buildContextResolver(ReflectionProvider $reflectionProvider, FilamentClassHelper $filamentClassHelper): ComponentContextResolver
{
    $config = new ParserConfig(usedAttributes: []);
    $lexer = new Lexer($config);
    $constExprParser = new ConstExprParser($config);
    $typeParser = new TypeParser($config, $constExprParser);
    $phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);

    return new ComponentContextResolver(
        $filamentClassHelper,
        new ResourceModelResolver($reflectionProvider, $filamentClassHelper),
        new AnnotationReader($lexer, $typeParser, $phpDocParser),
        new TableQueryRegistry,
        $reflectionProvider,
        new ModelReflectionHelper($reflectionProvider),
        new CustomComponentRegistry,
        new SchemaCallSiteRegistry,
    );
}

beforeAll(function () {
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $filamentClassHelper = new FilamentClassHelper($reflectionProvider);

    ConfigurableRuleTestCase::useRule(new RelationshipValidationRule(
        relationship: true,
        modelReflectionHelper: new ModelReflectionHelper($reflectionProvider),
        filamentClassHelper: $filamentClassHelper,
        componentContextResolver: buildContextResolver($reflectionProvider, $filamentClassHelper),
    ));
});

it('reports errors for invalid relationship names', function () {
    $this->analyse(
        [__DIR__.'/../Fixtures/App/RelationshipTests/RelationshipResource.php'],
        [
            ["'writer' is not a relationship on Fixtures\\App\\Models\\Post.", 26],
            ["'categorie' is not a relationship on Fixtures\\App\\Models\\Post.", 30],
        ]
    );
});

it('does not report errors for standalone classes without model context', function () {
    $this->analyse(
        [__DIR__.'/../Fixtures/App/RelationshipTests/ValidRelationships.php'],
        []
    );
});

it('skips validation when the rule is disabled', function () {
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $filamentClassHelper = new FilamentClassHelper($reflectionProvider);

    ConfigurableRuleTestCase::useRule(new RelationshipValidationRule(
        relationship: false,
        modelReflectionHelper: new ModelReflectionHelper($reflectionProvider),
        filamentClassHelper: $filamentClassHelper,
        componentContextResolver: buildContextResolver($reflectionProvider, $filamentClassHelper),
    ));

    $this->analyse(
        [__DIR__.'/../Fixtures/App/RelationshipTests/RelationshipResource.php'],
        []
    );
});

it('skips standalone invalid relationships without model context', function () {
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $filamentClassHelper = new FilamentClassHelper($reflectionProvider);

    ConfigurableRuleTestCase::useRule(new RelationshipValidationRule(
        relationship: true,
        modelReflectionHelper: new ModelReflectionHelper($reflectionProvider),
        filamentClassHelper: $filamentClassHelper,
        componentContextResolver: buildContextResolver($reflectionProvider, $filamentClassHelper),
    ));

    $this->analyse(
        [__DIR__.'/../Fixtures/App/RelationshipTests/InvalidRelationships.php'],
        []
    );
});
