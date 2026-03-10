<?php

use ImSuperlative\PhpstanFilament\FieldValidationLevel;
use ImSuperlative\PhpstanFilament\Tests\ConfigurableRuleTestCase;

beforeAll(function () {
    ConfigurableRuleTestCase::useRule(ConfigurableRuleTestCase::buildRule(FieldValidationLevel::Level_1));
});

it('returns no errors for non-relation dot-notation segments', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/MakeFieldResource.php'],
        []
    );
});

it('skips plain field names', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/ValidMakeFields.php'],
        []
    );
});

it('errors on unknown aggregate relation', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/AggregateFields.php'],
        [
            ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_count'.", 18],
            ["'fakething' is not a relationship on Fixtures\\App\\Models\\Post in aggregate field 'fakething_avg_score'.", 19],
        ]
    );
});

it('detects @property-read Model type as relation', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/PropertyReadRelation.php'],
        []
    );
});

it('returns no errors for nested typed property entries', function () {
    $this->analyse(
        [__DIR__.'/../../Fixtures/App/MakeFieldTests/NestedEntryFields.php'],
        []
    );
});
