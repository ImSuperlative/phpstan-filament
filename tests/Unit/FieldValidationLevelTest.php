<?php

use ImSuperlative\PhpstanFilament\FieldValidationLevel;

it('creates from int value', function () {
    expect(FieldValidationLevel::from(0))->toBe(FieldValidationLevel::Level_0)
        ->and(FieldValidationLevel::from(1))->toBe(FieldValidationLevel::Level_1)
        ->and(FieldValidationLevel::from(2))->toBe(FieldValidationLevel::Level_2)
        ->and(FieldValidationLevel::from(3))->toBe(FieldValidationLevel::Level_3);
});

it('reports enabled state', function () {
    expect(FieldValidationLevel::Level_0->isEnabled())->toBeFalse()
        ->and(FieldValidationLevel::Level_1->isEnabled())->toBeTrue()
        ->and(FieldValidationLevel::Level_2->isEnabled())->toBeTrue()
        ->and(FieldValidationLevel::Level_3->isEnabled())->toBeTrue();
});

it('gates plain field validation at level 2+', function () {
    expect(FieldValidationLevel::Level_0->shouldValidatePlainFields())->toBeFalse()
        ->and(FieldValidationLevel::Level_1->shouldValidatePlainFields())->toBeFalse()
        ->and(FieldValidationLevel::Level_2->shouldValidatePlainFields())->toBeTrue()
        ->and(FieldValidationLevel::Level_3->shouldValidatePlainFields())->toBeTrue();
});

it('gates unknown segment errors at level 2+', function () {
    expect(FieldValidationLevel::Level_1->shouldErrorOnUnknownSegment())->toBeFalse()
        ->and(FieldValidationLevel::Level_2->shouldErrorOnUnknownSegment())->toBeTrue();
});

it('gates typed property walking at level 3', function () {
    expect(FieldValidationLevel::Level_2->shouldWalkTypedProperties())->toBeFalse()
        ->and(FieldValidationLevel::Level_3->shouldWalkTypedProperties())->toBeTrue();
});

it('gates leaf validation at level 3', function () {
    expect(FieldValidationLevel::Level_2->shouldValidateLeaf())->toBeFalse()
        ->and(FieldValidationLevel::Level_3->shouldValidateLeaf())->toBeTrue();
});

it('gates aggregate column validation at level 3+', function () {
    expect(FieldValidationLevel::Level_1->shouldValidateAggregateColumn())->toBeFalse()
        ->and(FieldValidationLevel::Level_2->shouldValidateAggregateColumn())->toBeFalse()
        ->and(FieldValidationLevel::Level_3->shouldValidateAggregateColumn())->toBeTrue();
});
