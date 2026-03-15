<?php

/** @noinspection ClassConstantCanBeUsedInspection, StaticClosureCanBeUsedInspection */

use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\InjectionParameter;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\TypedInjectionMap;
use ImSuperlative\PhpstanFilament\Tests\Factories\InjectionMapFactoryFactory;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

function getTypedInjectionMap(): TypedInjectionMap
{
    return PhpstanTestCase::getContainer()->getByType(TypedInjectionMap::class);
}

/** Extract just the parameter names from the map (avoids OOM from serializing Type objects). */
function getParamNames(string $className): array
{
    $params = getTypedInjectionMap()->resolveForClass($className);

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
    $record = getTypedInjectionMap()->findParameter('Filament\Schemas\Components\Component', 'record');

    // Avoid passing InjectionParameter through expect() — PHPStan Types are huge objects
    $this->assertNotNull($record);
    $typeDescription = $record->type->describe(VerbosityLevel::typeOnly());
    expect($typeDescription)->toContain('Model');
});

it('includes evaluationIdentifier as self-type', function () {
    $action = getTypedInjectionMap()->findParameter('Filament\Actions\Action', 'action');

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
    $result = getTypedInjectionMap()->isTypeAllowed(
        'Filament\Schemas\Components\Component',
        new ObjectType('Filament\Schemas\Components\Utilities\Get'),
    );

    expect($result)->toBeTrue();
});

it('preserves method additions', function () {
    expect(getTypedInjectionMap()->getMethodAdditions('afterStateUpdated'))
        ->toContain('old', 'oldRaw');
});

it('merges user-provided closureInjectionMethods', function () {
    $map = PhpstanTestCase::getContainer()
        ->getByType(InjectionMapFactoryFactory::class)
        ->create(['customMethod' => ['customParam']])
        ->create();

    expect($map->getMethodAdditions('customMethod'))->toBe(['customParam']);
});
