<?php

use ImSuperlative\PhpstanFilament\Data\StateNarrowingRule;
use PHPStan\Type\FloatType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

it('creates a narrowing rule', function () {
    $type = TypeCombinator::addNull(new FloatType);
    $rule = new StateNarrowingRule(
        componentClass: 'Filament\Forms\Components\TextInput',
        methodName: 'numeric',
        narrowedType: $type,
    );

    expect($rule)
        ->componentClass->toBe('Filament\Forms\Components\TextInput')
        ->methodName->toBe('numeric')
        ->and($rule->narrowedType->describe(VerbosityLevel::precise()))->toBe('float|null');
});

it('checks if rule matches a component and method', function () {
    $rule = new StateNarrowingRule(
        componentClass: 'Filament\Forms\Components\TextInput',
        methodName: 'numeric',
        narrowedType: TypeCombinator::addNull(new FloatType),
    );

    expect($rule->matches('Filament\Forms\Components\TextInput', 'numeric'))->toBeTrue()
        ->and($rule->matches('Filament\Forms\Components\TextInput', 'email'))->toBeFalse()
        ->and($rule->matches('Filament\Forms\Components\Select', 'numeric'))->toBeFalse();
});
