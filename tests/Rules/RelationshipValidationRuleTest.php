<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Tests\Rules;

use ImSuperlative\PhpstanFilament\Tests\Factories\RelationshipValidationRuleFactory;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule<\PhpParser\Node>>
 */
class RelationshipValidationRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return self::getContainer()
            ->getByType(RelationshipValidationRuleFactory::class)
            ->create(relationship: true);
    }

    public function test_invalid_relationship_names(): void
    {
        $this->analyse(
            [project_root('tests/Fixtures/App/RelationshipTests/RelationshipResource.php')],
            [
                ["'writer' is not a relationship on Fixtures\\App\\Models\\Post.", 26],
                ["'categorie' is not a relationship on Fixtures\\App\\Models\\Post.", 30],
            ],
        );
    }

    public function test_no_errors_for_standalone_classes_without_model_context(): void
    {
        $this->analyse(
            [project_root('tests/Fixtures/App/RelationshipTests/ValidRelationships.php')],
            [],
        );
    }

    public function test_skips_standalone_invalid_relationships_without_model_context(): void
    {
        $this->analyse(
            [project_root('tests/Fixtures/App/RelationshipTests/InvalidRelationships.php')],
            [],
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            project_root('extension.neon'),
            tests_path('phpstan-test-services.neon'),
        ];
    }
}
