<?php

/** @noinspection ClassConstantCanBeUsedInspection, StaticClosureCanBeUsedInspection */

use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\InjectionParameter;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\TypedInjectionMap;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;

function injectionMapParamNames(string $className): array
{
    $params = PhpstanTestCase::getContainer()->getByType(TypedInjectionMap::class)
        ->resolveForClass($className);

    return $params !== null
        ? array_map(fn (InjectionParameter $p) => $p->name, $params)
        : [];
}

it('returns allowed parameters for Component class', function () {
    expect(injectionMapParamNames('Filament\Schemas\Components\Component'))
        ->toContain('state', 'get', 'set', 'record', 'livewire', 'model', 'operation');
});

it('returns allowed parameters for Column class', function () {
    expect(injectionMapParamNames('Filament\Tables\Columns\Column'))
        ->toContain('state', 'record', 'livewire', 'rowLoop', 'table')
        ->not->toContain('get', 'set');
});

it('returns allowed parameters for Action class', function () {
    expect(injectionMapParamNames('Filament\Actions\Action'))
        ->toContain('arguments', 'data', 'record', 'records', 'table', 'get', 'set');
});

it('returns empty for unknown class', function () {
    expect(injectionMapParamNames('App\Unknown'))->toBeEmpty();
});

it('returns method-specific additions for afterStateUpdated', function () {
    expect(
        PhpstanTestCase::getContainer()->getByType(TypedInjectionMap::class)->getMethodAdditions('afterStateUpdated')
    )->toContain('old', 'oldRaw');
});

it('resolves subclasses by walking parents', function () {
    expect(injectionMapParamNames('Filament\Forms\Components\TextInput'))
        ->toContain('state', 'get', 'set');
});

it('returns method-specific additions for sortable', function () {
    expect(PhpstanTestCase::getContainer()->getByType(TypedInjectionMap::class)->getMethodAdditions('sortable'))
        ->toContain('query', 'direction');
});

it('returns method-specific additions for disableOptionWhen', function () {
    expect(
        PhpstanTestCase::getContainer()->getByType(TypedInjectionMap::class)->getMethodAdditions('disableOptionWhen')
    )->toContain('value');
});

it('includes aliases in allowed parameters', function () {
    expect(injectionMapParamNames('Filament\Schemas\Components\Component'))
        ->toContain('context', 'operation');
});
