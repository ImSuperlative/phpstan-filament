<?php

use ImSuperlative\FilamentPhpstan\Parser\TypeStringParser;
use ImSuperlative\FilamentPhpstan\Resolvers\AnnotationReader;
use ImSuperlative\FilamentPhpstan\Resolvers\AttributeAnnotationParser;
use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\PhpDocAnnotationParser;
use ImSuperlative\FilamentPhpstan\Resolvers\ResourceModelResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\VirtualAnnotationProvider;
use ImSuperlative\FilamentPhpstan\Rules\RelationshipValidationRule;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use ImSuperlative\FilamentPhpstan\Support\ModelReflectionHelper;
use ImSuperlative\FilamentPhpstan\Tests\ConfigurableRuleTestCase;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

function buildContextResolver(ReflectionProvider $reflectionProvider, FilamentClassHelper $filamentClassHelper): ComponentContextResolver
{
    $typeStringParser = TypeStringParser::make();

    $modelReflectionHelper = new ModelReflectionHelper($reflectionProvider);
    $resourceModelResolver = new ResourceModelResolver($reflectionProvider, $filamentClassHelper, $modelReflectionHelper);

    $annotationReader = new AnnotationReader(
        new AttributeAnnotationParser($typeStringParser),
        new PhpDocAnnotationParser($typeStringParser),
    );

    return new ComponentContextResolver(
        $filamentClassHelper,
        $resourceModelResolver,
        $annotationReader,
        $reflectionProvider,
        $modelReflectionHelper,
        new VirtualAnnotationProvider(
            enabled: false,
            filamentPath: [],
            currentWorkingDirectory: '',
            analysedPaths: [],
            analysedPathsFromConfig: [],
            resourceModelResolver: $resourceModelResolver,
        ),
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
