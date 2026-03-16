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
class MakeFieldLevel2Test extends RuleTestCase
{

    protected function getRule(): Rule
    {
        $container = self::getContainer();
        $level = FieldValidationLevel::Level_2;

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

    public function test_reports_plain_field_not_on_model(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/StrictValidationResource.php')],
            [
                ["'nonexistent' does not exist on Fixtures\\App\\Models\\Post.", 18],
            ],
        );
    }

    public function test_does_not_validate_form_fields(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/FormFieldsExcluded.php')],
            [],
        );
    }

    public function test_skips_virtual_columns_with_state(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/VirtualColumnResource.php')],
            [],
        );
    }

    public function test_resolves_related_model_for_manage_related_records_page(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/ManagePostComments.php')],
            [
                ["'nonexistent' does not exist on Fixtures\\App\\Models\\Comment.", 26],
            ],
        );
    }

    public function test_skips_all_validation_when_table_uses_records(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/RecordsTablePage.php')],
            [],
        );
    }

    public function test_validates_fields_inside_custom_component_using_collector_inferred_model(): void
    {
        $this->analyse(
            [
                fixture_path('App/CustomComponents/CreatedAtEntry.php'),
                fixture_path('App/CustomComponents/EmailDeliveryGroup.php'),
            ],
            [],
        );
    }

    public function test_does_not_error_on_morph_to_dot_notation_fields(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/MorphToFields.php')],
            [],
        );
    }

    public function test_skips_validation_for_custom_component_with_no_model_context(): void
    {
        $this->analyse(
            [fixture_path('App/CustomComponents/EmailDeliveryGroup.php')],
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
