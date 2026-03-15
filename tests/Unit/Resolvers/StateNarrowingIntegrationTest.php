<?php

use ImSuperlative\PhpstanFilament\Resolvers\FormComponentStateMap;
use ImSuperlative\PhpstanFilament\Resolvers\FormComponentTypeNarrower;
use ImSuperlative\PhpstanFilament\Resolvers\FormOptionsNarrower;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;

beforeEach(function () {
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $this->map = new FormComponentStateMap($reflectionProvider);
    $this->narrower = new FormComponentTypeNarrower($reflectionProvider, new FormOptionsNarrower);
});

it('narrows TextInput from base type through numeric()', function () {
    $baseType = $this->map->resolve('Filament\Forms\Components\TextInput');
    expect($baseType?->describe(VerbosityLevel::precise()))->toBe('string|null', basename(__FILE__).':'.__LINE__);

    $narrowed = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\TextInput',
        baseType: $baseType,
        methodCalls: ['required', 'numeric', 'maxLength'],
    );
    expect($narrowed->describe(VerbosityLevel::precise()))->toBe('float|null', basename(__FILE__).':'.__LINE__);
});

it('narrows Select from base type through multiple()', function () {
    $baseType = $this->map->resolve('Filament\Forms\Components\Select');
    expect($baseType?->describe(VerbosityLevel::precise()))->toBe('int|string|null', basename(__FILE__).':'.__LINE__);

    $narrowed = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\Select',
        baseType: $baseType,
        methodCalls: ['options', 'multiple', 'searchable'],
    );
    expect($narrowed->describe(VerbosityLevel::precise()))->toBe('array<int, int|string>', basename(__FILE__).':'.__LINE__);
});

it('does not narrow TextInput with non-narrowing methods', function () {
    $baseType = $this->map->resolve('Filament\Forms\Components\TextInput');

    $narrowed = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\TextInput',
        baseType: $baseType,
        methodCalls: ['required', 'email', 'maxLength', 'placeholder'],
    );
    expect($narrowed->describe(VerbosityLevel::precise()))->toBe('string|null');
});

it('does not narrow Toggle (no narrowing rules exist)', function () {
    $baseType = $this->map->resolve('Filament\Forms\Components\Toggle');
    expect($baseType?->describe(VerbosityLevel::precise()))->toBe('bool|null', basename(__FILE__).':'.__LINE__);

    $narrowed = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\Toggle',
        baseType: $baseType,
        methodCalls: ['required', 'inline'],
    );
    expect($narrowed->describe(VerbosityLevel::precise()))->toBe('bool|null');
});

it('handles full pipeline for FileUpload multiple', function () {
    $baseType = $this->map->resolve('Filament\Forms\Components\FileUpload');
    expect($baseType?->describe(VerbosityLevel::precise()))->toBe('string|null', basename(__FILE__).':'.__LINE__);

    $narrowed = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\FileUpload',
        baseType: $baseType,
        methodCalls: ['multiple', 'image', 'maxSize'],
    );
    expect($narrowed->describe(VerbosityLevel::precise()))->toBe('array<int, string>|null', basename(__FILE__).':'.__LINE__);
});

it('handles full pipeline for Radio boolean', function () {
    $baseType = $this->map->resolve('Filament\Forms\Components\Radio');
    expect($baseType?->describe(VerbosityLevel::precise()))->toBe('int|string|null', basename(__FILE__).':'.__LINE__);

    $narrowed = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\Radio',
        baseType: $baseType,
        methodCalls: ['boolean', 'inline'],
    );
    expect($narrowed->describe(VerbosityLevel::precise()))->toBe('int|null', basename(__FILE__).':'.__LINE__);
});
