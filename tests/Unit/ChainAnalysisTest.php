<?php

// tests/Unit/ChainAnalysisTest.php

use ImSuperlative\FilamentPhpstan\Data\ChainAnalysis;

it('stores all chain analysis fields', function () {
    $analysis = new ChainAnalysis(
        componentClass: 'Filament\Forms\Components\TextInput',
        methodCalls: ['numeric', 'required'],
        enumClass: null,
        literalOptionKeys: null,
        isMultiple: false,
        fieldName: 'price',
    );

    expect($analysis->componentClass)->toBe('Filament\Forms\Components\TextInput')
        ->and($analysis->methodCalls)->toBe(['numeric', 'required'])
        ->and($analysis->enumClass)->toBeNull()
        ->and($analysis->literalOptionKeys)->toBeNull()
        ->and($analysis->isMultiple)->toBeFalse()
        ->and($analysis->fieldName)->toBe('price');
});

it('stores enum class and isMultiple for enum selects', function () {
    $analysis = new ChainAnalysis(
        componentClass: 'Filament\Forms\Components\Select',
        methodCalls: ['enum', 'multiple'],
        enumClass: 'Fixtures\App\Enums\PostStatus',
        literalOptionKeys: null,
        isMultiple: true,
        fieldName: 'status',
    );

    expect($analysis->enumClass)->toBe('Fixtures\App\Enums\PostStatus')
        ->and($analysis->isMultiple)->toBeTrue();
});

it('stores literal option keys', function () {
    $analysis = new ChainAnalysis(
        componentClass: 'Filament\Forms\Components\Select',
        methodCalls: ['options'],
        enumClass: null,
        literalOptionKeys: ['draft', 'published', 'archived'],
        isMultiple: false,
        fieldName: 'status',
    );

    expect($analysis->literalOptionKeys)->toBe(['draft', 'published', 'archived']);
});

it('allows null componentClass for unresolvable chains', function () {
    $analysis = new ChainAnalysis(
        componentClass: null,
        methodCalls: [],
        enumClass: null,
        literalOptionKeys: null,
        isMultiple: false,
        fieldName: null,
    );

    expect($analysis->componentClass)->toBeNull()
        ->and($analysis->fieldName)->toBeNull();
});
