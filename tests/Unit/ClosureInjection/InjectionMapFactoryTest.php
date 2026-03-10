<?php

use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\InjectionMapFactory;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\InjectionParameter;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\TypedInjectionMap;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\DiscoveredClassCache;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\VendorAstParser;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;

function getSharedMap(): TypedInjectionMap
{
    static $map = null;

    if ($map === null) {
        $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
        $factory = new InjectionMapFactory($reflectionProvider, new VendorAstParser, new DiscoveredClassCache);
        $map = $factory->create();
    }

    return $map;
}

/** Extract just the parameter names from the map (avoids OOM from serializing Type objects). */
function getParamNames(string $className): array
{
    $params = getSharedMap()->resolveForClass($className);

    return $params !== null
        ? array_map(fn (InjectionParameter $p) => $p->name, $params)
        : [];
}

it('creates a typed injection map with Component entries', function () {
    $names = getParamNames('Filament\Schemas\Components\Component');

    expect($names)->toContain('state', 'record', 'livewire', 'model', 'get', 'set', 'component');
});

it('creates a typed injection map with Action entries', function () {
    $names = getParamNames('Filament\Actions\Action');

    expect($names)->toContain('arguments', 'data', 'record', 'records', 'table', 'action');
});

it('resolves types for parameters', function () {
    $record = getSharedMap()->findParameter('Filament\Schemas\Components\Component', 'record');

    // Avoid passing InjectionParameter through expect() — PHPStan Types are huge objects
    $this->assertNotNull($record);
    $typeDescription = $record->type->describe(\PHPStan\Type\VerbosityLevel::typeOnly());
    expect($typeDescription)->toContain('Model');
});

it('includes evaluationIdentifier as self-type', function () {
    $action = getSharedMap()->findParameter('Filament\Actions\Action', 'action');

    $this->assertNotNull($action);
    $this->assertSame('action', $action->name);
});

it('discovers all expected vendor classes', function () {
    expect(getParamNames('Filament\Schemas\Components\Component'))->not->toBeEmpty()
        ->and(getParamNames('Filament\Tables\Columns\Column'))->not->toBeEmpty()
        ->and(getParamNames('Filament\Actions\Action'))->not->toBeEmpty()
        ->and(getParamNames('Filament\Tables\Table'))->not->toBeEmpty()
        ->and(getParamNames('Filament\Tables\Filters\BaseFilter'))->not->toBeEmpty()
        ->and(getParamNames('Filament\Tables\Grouping\Group'))->not->toBeEmpty()
        ->and(getParamNames('Filament\Tables\Columns\Summarizers\Summarizer'))->not->toBeEmpty()
        ->and(getParamNames('Filament\Actions\Imports\ImportColumn'))->not->toBeEmpty()
        ->and(getParamNames('Filament\Actions\Exports\ExportColumn'))->not->toBeEmpty();
});

it('resolves subclasses via parent walk', function () {
    $names = getParamNames('Filament\Forms\Components\TextInput');

    expect($names)->toContain('state', 'get', 'set');
});

it('includes type map entries for Get and Set', function () {
    $result = getSharedMap()->isTypeAllowed(
        'Filament\Schemas\Components\Component',
        new ObjectType('Filament\Schemas\Components\Utilities\Get'),
    );

    expect($result)->toBeTrue();
});

it('preserves method additions', function () {
    expect(getSharedMap()->getMethodAdditions('afterStateUpdated'))
        ->toContain('old', 'oldRaw');
});

it('merges user-provided closureInjectionMethods', function () {
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $factory = new InjectionMapFactory($reflectionProvider, new VendorAstParser, new DiscoveredClassCache, [
        'customMethod' => ['customParam'],
    ]);
    $map = $factory->create();

    expect($map->getMethodAdditions('customMethod'))->toBe(['customParam']);
});
