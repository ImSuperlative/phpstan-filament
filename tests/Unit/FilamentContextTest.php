<?php

// tests/Unit/FilamentContextTest.php

use ImSuperlative\FilamentPhpstan\Data\FilamentContext;

it('creates a context with all fields', function () {
    $context = new FilamentContext(
        componentClass: 'Filament\Forms\Components\TextInput',
        resourceClass: 'App\Filament\Resources\FormResource',
        modelClass: 'App\Models\Form',
        isNested: false,
    );

    expect($context)
        ->componentClass->toBe('Filament\Forms\Components\TextInput')
        ->resourceClass->toBe('App\Filament\Resources\FormResource')
        ->modelClass->toBe('App\Models\Form')
        ->isNested->toBeFalse();
});

it('creates a partial context with nulls', function () {
    $context = new FilamentContext(
        componentClass: 'Filament\Forms\Components\TextInput',
    );

    expect($context)
        ->componentClass->toBe('Filament\Forms\Components\TextInput')
        ->resourceClass->toBeNull()
        ->modelClass->toBeNull()
        ->isNested->toBeFalse();
});

it('reports whether model context is available', function () {
    $withModel = new FilamentContext(modelClass: 'App\Models\Form');
    $without = new FilamentContext;

    expect($withModel->hasModelContext())->toBeTrue()
        ->and($without->hasModelContext())->toBeFalse();
});

it('reports whether component context is available', function () {
    $with = new FilamentContext(componentClass: 'Filament\Forms\Components\TextInput');
    $without = new FilamentContext;

    expect($with->hasComponentContext())->toBeTrue()
        ->and($without->hasComponentContext())->toBeFalse();
});
