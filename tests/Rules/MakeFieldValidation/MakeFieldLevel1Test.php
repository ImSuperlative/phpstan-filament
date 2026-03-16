<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Tests\Rules\MakeFieldValidation;

use ImSuperlative\PhpstanFilament\FieldValidationLevel;
use ImSuperlative\PhpstanFilament\Rules\MakeFieldValidation\MakeFieldValidationRule;
use ImSuperlative\PhpstanFilament\Tests\Factories\AggregateFieldValidatorFactory;
use ImSuperlative\PhpstanFilament\Tests\Factories\MakeFieldValidationRuleFactory;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MakeFieldValidationRule>
 */
class MakeFieldLevel1Test extends RuleTestCase
{

    protected function getRule(): Rule
    {
        $container = self::getContainer();
        $level = FieldValidationLevel::Level_1;

        $validatorFactory = $container
            ->getByType(AggregateFieldValidatorFactory::class)
            ->create($level);

        return $container
            ->getByType(MakeFieldValidationRuleFactory::class)
            ->create($level, $validatorFactory);
    }

    public function test_no_errors_for_non_relation_dot_notation_segments(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/MakeFieldResource.php')],
            [],
        );
    }

    public function test_skips_plain_field_names(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/ValidMakeFields.php')],
            [],
        );
    }

    public function test_errors_on_unknown_aggregate_relation(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/AggregateFields.php')],
            [
                ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_count'.", 18],
                ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_avg_score'.", 19],
            ],
        );
    }

    public function test_detects_property_read_model_type_as_relation(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/PropertyReadRelation.php')],
            [],
        );
    }

    public function test_no_errors_for_nested_typed_property_entries(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/NestedEntryFields.php')],
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
