<?php

use ImSuperlative\PhpstanFilament\Parser\TypeStringParser;
use ImSuperlative\PhpstanFilament\Resolvers\AnnotationReader;
use ImSuperlative\PhpstanFilament\Resolvers\AttributeAnnotationParser;
use ImSuperlative\PhpstanFilament\Resolvers\ComponentContextResolver;
use ImSuperlative\PhpstanFilament\Resolvers\PhpDocAnnotationParser;
use ImSuperlative\PhpstanFilament\Resolvers\ResourceModelResolver;
use ImSuperlative\PhpstanFilament\Resolvers\VirtualAnnotationProvider;
use ImSuperlative\PhpstanFilament\Rules\RelationshipValidationRule;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use ImSuperlative\PhpstanFilament\Support\ModelReflectionHelper;
use ImSuperlative\PhpstanFilament\Tests\ConfigurableRuleTestCase;
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

function makeRelationshipRule(): RelationshipValidationRule
{
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $filamentClassHelper = new FilamentClassHelper($reflectionProvider);

    return new RelationshipValidationRule(
        relationship: true,
        modelReflectionHelper: new ModelReflectionHelper($reflectionProvider),
        filamentClassHelper: $filamentClassHelper,
        componentContextResolver: buildContextResolver($reflectionProvider, $filamentClassHelper),
    );
}

it('reports errors for invalid relationship names', function () {
    ConfigurableRuleTestCase::useRule(makeRelationshipRule());
    $this->analyse(
        [__DIR__.'/../Fixtures/App/RelationshipTests/RelationshipResource.php'],
        [
            ["'writer' is not a relationship on Fixtures\\App\\Models\\Post.", 26],
            ["'categorie' is not a relationship on Fixtures\\App\\Models\\Post.", 30],
        ]
    );
});

it('does not report errors for standalone classes without model context', function () {
    ConfigurableRuleTestCase::useRule(makeRelationshipRule());
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
