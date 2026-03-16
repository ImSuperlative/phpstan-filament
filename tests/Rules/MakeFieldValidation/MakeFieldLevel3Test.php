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
class MakeFieldLevel3Test extends RuleTestCase
{

    protected function getRule(): Rule
    {
        $container = self::getContainer();
        $level = FieldValidationLevel::Level_3;

        $validatorFactory = $container
            ->getByType(AggregateFieldValidatorFactory::class)
            ->create($level);

        return $container
            ->getByType(MakeFieldValidationRuleFactory::class)
            ->create($level, $validatorFactory);
    }

    public function test_validates_aggregate_column_on_related_model(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/AggregateFields.php')],
            [
                ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_count'.", 18],
                ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_avg_score'.", 19],
                ["'rating' does not exist on Fixtures\\App\\Models\\Comment in aggregate field 'comments_avg_rating'.", 20],
            ],
        );
    }

    public function test_validates_full_dot_path_including_leaf_and_typed_properties(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/FullPathValidation.php')],
            [
                ["'nonexistent' does not exist on Fixtures\\App\\Models\\Author in dot-notation field 'author.nonexistent'.", 36],
                ["'nonexistent' does not exist on Fixtures\\App\\Models\\Author in dot-notation field 'comments.post.author.nonexistent'.", 39],
                ["'nonexistent_field' does not exist on Fixtures\\App\\Data\\PostOptions in dot-notation field 'options.nonexistent_field'.", 42],
                ["'fakething' is not a relationship or typed property on Fixtures\\App\\Models\\Post in dot-notation field 'fakething.name'.", 45],
                ["'nonexistent_field' does not exist on Fixtures\\App\\Data\\PostMeta in dot-notation field 'options.meta.nonexistent_field'.", 48],
            ],
        );
    }

    public function test_validates_leaf_fields_inside_custom_component(): void
    {
        $this->analyse(
            [fixture_path('App/CustomComponents/EmailDeliveryGroup.php')],
            [
                ["'nonexistent_field' does not exist on Fixtures\\App\\Models\\Email in dot-notation field 'latestSubmissionEmail.nonexistent_field'.", 28],
            ],
        );
    }

    public function test_filament_field_annotation_overrides_segment_type_resolution(): void
    {
        $this->analyse(
            [fixture_path('App/CustomComponents/AnnotatedHelper.php')],
            [
                ["'nonexistent_field' does not exist on Fixtures\\App\\Models\\Email in dot-notation field 'latestSubmissionEmail.nonexistent_field'.", 21],
            ],
        );
    }

    public function test_validates_nested_typed_property_entries(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/NestedEntryFields.php')],
            [
                ["'nonexistent_field' does not exist on Fixtures\\App\\Data\\PostMeta in dot-notation field 'options.meta.nonexistent_field'.", 39],
                ["'nonexistent' does not exist on Fixtures\\App\\Models\\Author in dot-notation field 'author.nonexistent'.", 46],
            ],
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
