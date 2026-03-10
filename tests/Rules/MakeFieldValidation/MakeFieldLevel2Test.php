<?php

use ImSuperlative\PhpstanFilament\FieldValidationLevel;
use ImSuperlative\PhpstanFilament\Tests\ConfigurableRuleTestCase;

beforeAll(function () {
    ConfigurableRuleTestCase::useRule(ConfigurableRuleTestCase::buildRule(FieldValidationLevel::Level_2));
});

it('returns no errors for non-relation dot-notation segments', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/MakeFieldResource.php'],
        []
    );
});

it('errors on unknown aggregate relation, column not checked', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/AggregateFields.php'],
        [
            ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_count'.", 18],
            ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_avg_score'.", 19],
        ]
    );
});

it('reports plain field not on model', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/StrictValidationResource.php'],
        [
            ["'nonexistent' does not exist on Fixtures\\App\\Models\\Post.", 18],
        ]
    );
});

it('does not validate form fields', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/FormFieldsExcluded.php'],
        []
    );
});

it('skips virtual columns with ->state()', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/VirtualColumnResource.php'],
        []
    );
});

it('resolves related model for ManageRelatedRecords page', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/ManagePostComments.php'],
        [
            ["'nonexistent' does not exist on Fixtures\\App\\Models\\Comment.", 26],
        ]
    );
});

it('skips all validation when table uses ->records()', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/RecordsTablePage.php'],
        []
    );
});

it('validates fields inside custom component using collector-inferred model', function () {
    $this->analyse(
        [
            __DIR__.'/../../Fixtures/App/CustomComponents/CreatedAtEntry.php',
            __DIR__.'/../../Fixtures/App/CustomComponents/EmailDeliveryGroup.php',
        ],
        []
    );
});

it('does not error on morphTo dot-notation fields', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/MorphToFields.php'],
        []
    );
});

it('skips validation for custom component with no model context', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/CustomComponents/EmailDeliveryGroup.php'],
        []
    );
});
