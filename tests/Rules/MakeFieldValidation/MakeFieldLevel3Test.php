<?php

use ImSuperlative\PhpstanFilament\FieldValidationLevel;
use ImSuperlative\PhpstanFilament\Tests\ConfigurableRuleTestCase;

beforeAll(function () {
    ConfigurableRuleTestCase::useRule(ConfigurableRuleTestCase::buildRule(FieldValidationLevel::Level_3));
});

it('validates aggregate column on related model', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/AggregateFields.php'],
        [
            ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_count'.", 18],
            ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_avg_score'.", 19],
            ["'rating' does not exist on Fixtures\\App\\Models\\Comment in aggregate field 'comments_avg_rating'.", 20],
        ]
    );
});

it('validates full dot path including leaf and typed properties', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/FullPathValidation.php'],
        [
            ["'nonexistent' does not exist on Fixtures\\App\\Models\\Author in dot-notation field 'author.nonexistent'.", 36],
            ["'nonexistent' does not exist on Fixtures\\App\\Models\\Author in dot-notation field 'comments.post.author.nonexistent'.", 39],
            ["'nonexistent_field' does not exist on Fixtures\\App\\Data\\PostOptions in dot-notation field 'options.nonexistent_field'.", 42],
            ["'fakething' is not a relationship or typed property on Fixtures\\App\\Models\\Post in dot-notation field 'fakething.name'.", 45],
            ["'nonexistent_field' does not exist on Fixtures\\App\\Data\\PostMeta in dot-notation field 'options.meta.nonexistent_field'.", 48],
        ]
    );
});

it('validates leaf fields inside custom component', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/CustomComponents/EmailDeliveryGroup.php'],
        [
            ["'nonexistent_field' does not exist on Fixtures\\App\\Models\\Email in dot-notation field 'latestSubmissionEmail.nonexistent_field'.", 28],
        ]
    );
});

it('@filament-field overrides segment type resolution', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/CustomComponents/AnnotatedHelper.php'],
        [
            ["'nonexistent_field' does not exist on Fixtures\App\Models\Email in dot-notation field 'latestSubmissionEmail.nonexistent_field'.", 21],
        ]
    );
});

it('validates nested typed property entries', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/NestedEntryFields.php'],
        [
            ["'nonexistent_field' does not exist on Fixtures\\App\\Data\\PostMeta in dot-notation field 'options.meta.nonexistent_field'.", 39],
            ["'nonexistent' does not exist on Fixtures\\App\\Models\\Author in dot-notation field 'author.nonexistent'.", 46],
        ]
    );
});
