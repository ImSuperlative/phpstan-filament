<?php

use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\InjectionMapFactory;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\InjectionParameter;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\TypedInjectionMap;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\DiscoveredClassCache;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\VendorAstParser;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

function getMap(): TypedInjectionMap
{
    static $map = null;

    if ($map === null) {
        $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
        $factory = new InjectionMapFactory($reflectionProvider, new VendorAstParser, new DiscoveredClassCache);
        $map = $factory->create();
    }

    return $map;
}

function paramNames(string $className): array
{
    $params = getMap()->resolveForClass($className);

    return $params !== null
        ? array_map(fn (InjectionParameter $p) => $p->name, $params)
        : [];
}

it('returns allowed parameters for Component class', function () {
    expect(paramNames('Filament\Schemas\Components\Component'))
        ->toContain('state', 'get', 'set', 'record', 'livewire', 'model', 'operation');
});

it('returns allowed parameters for Column class', function () {
    expect(paramNames('Filament\Tables\Columns\Column'))
        ->toContain('state', 'record', 'livewire', 'rowLoop', 'table')
        ->not->toContain('get', 'set');
});

it('returns allowed parameters for Action class', function () {
    expect(paramNames('Filament\Actions\Action'))
        ->toContain('arguments', 'data', 'record', 'records', 'table', 'get', 'set');
});

it('returns empty for unknown class', function () {
    expect(paramNames('App\Unknown'))->toBeEmpty();
});

it('returns method-specific additions for afterStateUpdated', function () {
    expect(getMap()->getMethodAdditions('afterStateUpdated'))
        ->toContain('old', 'oldRaw');
});

it('resolves subclasses by walking parents', function () {
    expect(paramNames('Filament\Forms\Components\TextInput'))
        ->toContain('state', 'get', 'set');
});

it('returns method-specific additions for sortable', function () {
    expect(getMap()->getMethodAdditions('sortable'))
        ->toContain('query', 'direction');
});

it('returns method-specific additions for disableOptionWhen', function () {
    expect(getMap()->getMethodAdditions('disableOptionWhen'))
        ->toContain('value');
});

it('includes aliases in allowed parameters', function () {
    expect(paramNames('Filament\Schemas\Components\Component'))
        ->toContain('context', 'operation');
});
