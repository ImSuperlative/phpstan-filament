<?php

use ImSuperlative\FilamentPhpstan\Rules\ClosureInjection\InjectionParameter;
use ImSuperlative\FilamentPhpstan\Rules\ClosureInjection\TypedInjectionMap;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;

beforeEach(function () {
    $this->reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
});

it('resolves parameters for an exact class', function () {
    $map = new TypedInjectionMap(
        classMap: [
            'Filament\Tables\Columns\Column' => [
                new InjectionParameter('state', new MixedType),
                new InjectionParameter('record', new ObjectType('Illuminate\Database\Eloquent\Model')),
            ],
        ],
        typeMap: [],
        methodAdditions: [],
        reflectionProvider: $this->reflectionProvider,
    );

    $params = $map->resolveForClass('Filament\Tables\Columns\Column');

    expect($params)->toHaveCount(2)
        ->and($params[0]->name)->toBe('state')
        ->and($params[1]->name)->toBe('record');
});

it('resolves parameters by walking parent classes', function () {
    $map = new TypedInjectionMap(
        classMap: [
            'Filament\Schemas\Components\Component' => [
                new InjectionParameter('state', new MixedType),
                new InjectionParameter('get', new ObjectType('Filament\Schemas\Components\Utilities\Get')),
            ],
        ],
        typeMap: [],
        methodAdditions: [],
        reflectionProvider: $this->reflectionProvider,
    );

    $params = $map->resolveForClass('Filament\Forms\Components\TextInput');

    expect($params)->not->toBeNull()
        ->and(collect($params)->pluck('name')->toArray())->toContain('state', 'get');
});

it('returns null for unknown classes', function () {
    $map = new TypedInjectionMap(
        classMap: [],
        typeMap: [],
        methodAdditions: [],
        reflectionProvider: $this->reflectionProvider,
    );

    expect($map->resolveForClass('App\Unknown'))->toBeNull();
});

it('checks type map for allowed types', function () {
    $map = new TypedInjectionMap(
        classMap: [],
        typeMap: [
            'Filament\Schemas\Components\Component' => [
                new InjectionParameter('Filament\Schemas\Components\Utilities\Get', new ObjectType('Filament\Schemas\Components\Utilities\Get')),
            ],
        ],
        methodAdditions: [],
        reflectionProvider: $this->reflectionProvider,
    );

    expect($map->isTypeAllowed(
        'Filament\Schemas\Components\Component',
        new ObjectType('Filament\Schemas\Components\Utilities\Get'),
    ))->toBeTrue();

    expect($map->isTypeAllowed(
        'Filament\Schemas\Components\Component',
        new StringType,
    ))->toBeFalse();
});

it('returns method additions', function () {
    $map = new TypedInjectionMap(
        classMap: [],
        typeMap: [],
        methodAdditions: ['afterStateUpdated' => ['old', 'oldRaw']],
        reflectionProvider: $this->reflectionProvider,
    );

    expect($map->getMethodAdditions('afterStateUpdated'))->toBe(['old', 'oldRaw'])
        ->and($map->getMethodAdditions('unknownMethod'))->toBe([]);
});

it('finds parameter by name and returns its type', function () {
    $modelType = new ObjectType('Illuminate\Database\Eloquent\Model');
    $map = new TypedInjectionMap(
        classMap: [
            'Filament\Tables\Columns\Column' => [
                new InjectionParameter('record', $modelType),
            ],
        ],
        typeMap: [],
        methodAdditions: [],
        reflectionProvider: $this->reflectionProvider,
    );

    $found = $map->findParameter('Filament\Tables\Columns\Column', 'record');
    expect($found)->toBeInstanceOf(InjectionParameter::class)
        ->and($found->type)->toBe($modelType);
});
