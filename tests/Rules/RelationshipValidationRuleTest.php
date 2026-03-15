<?php

use ImSuperlative\PhpstanFilament\Tests\ConfigurableRuleTestCase;
use ImSuperlative\PhpstanFilament\Tests\Factories\RelationshipValidationRuleFactory;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;

function getRelationshipFactory(): RelationshipValidationRuleFactory
{
    static $factory = null;

    return $factory ??= PhpstanTestCase::getContainer()->getByType(RelationshipValidationRuleFactory::class);
}

it('reports errors for invalid relationship names', function () {
    ConfigurableRuleTestCase::useRule(getRelationshipFactory()->create(relationship: true));
    $this->analyse(
        [project_root('tests/Fixtures/App/RelationshipTests/RelationshipResource.php')],
        [
            ["'writer' is not a relationship on Fixtures\\App\\Models\\Post.", 26],
            ["'categorie' is not a relationship on Fixtures\\App\\Models\\Post.", 30],
        ]
    );
});

it('does not report errors for standalone classes without model context', function () {
    ConfigurableRuleTestCase::useRule(getRelationshipFactory()->create(relationship: true));
    $this->analyse(
        [project_root('tests/Fixtures/App/RelationshipTests/ValidRelationships.php')],
        []
    );
});

it('skips validation when the rule is disabled', function () {
    ConfigurableRuleTestCase::useRule(getRelationshipFactory()->create(relationship: false));
    $this->analyse(
        [project_root('tests/Fixtures/App/RelationshipTests/RelationshipResource.php')],
        []
    );
});

it('skips standalone invalid relationships without model context', function () {
    ConfigurableRuleTestCase::useRule(getRelationshipFactory()->create(relationship: true));
    $this->analyse(
        [project_root('tests/Fixtures/App/RelationshipTests/InvalidRelationships.php')],
        []
    );
});
