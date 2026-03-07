<?php

// tests/Unit/ComponentStateMapTest.php

use ImSuperlative\FilamentPhpstan\Resolvers\FormComponentStateMap;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;

beforeEach(function () {
    $this->map = new FormComponentStateMap(
        PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class),
    );
});

it('returns state type for TextInput', function () {
    expect($this->map->resolve('Filament\Forms\Components\TextInput')?->describe(VerbosityLevel::precise()))
        ->toBe('string|null');
});

it('returns state type for Toggle', function () {
    expect($this->map->resolve('Filament\Forms\Components\Toggle')?->describe(VerbosityLevel::precise()))
        ->toBe('bool|null');
});

it('returns state type for CheckboxList', function () {
    expect($this->map->resolve('Filament\Forms\Components\CheckboxList')?->describe(VerbosityLevel::precise()))
        ->toBe('array<int, int|string>');
});

it('returns null for unknown component', function () {
    expect($this->map->resolve('App\Custom\Unknown'))
        ->toBeNull();
});

it('resolves subclasses by checking parent classes', function () {
    expect($this->map->resolveForClass('Filament\Forms\Components\TextInput')?->describe(VerbosityLevel::precise()))
        ->toBe('string|null');
});

it('returns corrected type for Select (single)', function () {
    expect($this->map->resolve('Filament\Forms\Components\Select')?->describe(VerbosityLevel::precise()))
        ->toBe('int|string|null');
});

it('returns corrected type for CheckboxList', function () {
    expect($this->map->resolve('Filament\Forms\Components\CheckboxList')?->describe(VerbosityLevel::precise()))
        ->toBe('array<int, int|string>');
});

it('returns corrected type for Radio', function () {
    expect($this->map->resolve('Filament\Forms\Components\Radio')?->describe(VerbosityLevel::precise()))
        ->toBe('int|string|null');
});

it('returns state type for ToggleButtons', function () {
    expect($this->map->resolve('Filament\Forms\Components\ToggleButtons')?->describe(VerbosityLevel::precise()))
        ->toBe('int|string|null');
});
