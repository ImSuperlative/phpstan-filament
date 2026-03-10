<?php

use ImSuperlative\PhpstanFilament\Resolvers\ComponentContextResolver;
use ImSuperlative\PhpstanFilament\Resolvers\StateTypeResolver;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\ClosureInjectionRule;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\TypedInjectionMap;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use ImSuperlative\PhpstanFilament\Tests\ConfigurableRuleTestCase;
use PHPStan\Reflection\ReflectionProvider;

function getEnabledRule(): ClosureInjectionRule
{
    return ConfigurableRuleTestCase::getContainer()->getByType(ClosureInjectionRule::class);
}

function makeDisabledRule(): ClosureInjectionRule
{
    $container = ConfigurableRuleTestCase::getContainer();

    return new ClosureInjectionRule(
        closureInjection: false,
        reservedClosureInjection: false,
        injectionMap: $container->getByType(TypedInjectionMap::class),
        filamentClassHelper: $container->getByType(FilamentClassHelper::class),
        reflectionProvider: $container->getByType(ReflectionProvider::class),
        stateTypeResolver: $container->getByType(StateTypeResolver::class),
        componentContextResolver: $container->getByType(ComponentContextResolver::class),
    );
}

it('does not report errors for valid closure injections', function () {
    ConfigurableRuleTestCase::useRule(getEnabledRule());
    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/InjectionValid.php'],
        [],
    );
});

it('reports errors for all invalid closure injections', function () {
    ConfigurableRuleTestCase::useRule(getEnabledRule());
    $componentParams = '$context, $operation, $get, $livewire, $model, $parentRepeaterItemIndex, $rawState, $record, $set, $state, $component';
    $columnParams = '$livewire, $record, $rowLoop, $state, $table, $column, $value';
    $columnNoValueParams = '$livewire, $record, $rowLoop, $state, $table, $column';
    $actionParams = '$arguments, $data, $livewire, $model, $mountedActions, $record, $selectedRecords, $records, $selectedRecordsQuery, $recordsQuery, $schema, $schemaComponent, $component, $schemaGet, $get, $schemaSet, $set, $schemaComponentState, $state, $schemaState, $table, $action';

    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/InjectionInvalid.php'],
        [
            ["Closure parameter '\$old' is not a valid injection for this context. Valid parameters: {$componentParams}.", 21],
            ["Closure parameter '\$rowLoop' is not a valid injection for this context. Valid parameters: {$componentParams}.", 25],
            ["Closure parameter '\$table' is not a valid injection for this context. Valid parameters: {$componentParams}.", 29],
            ["Closure parameter '\$nonexistent' is not a valid injection for this context. Valid parameters: {$componentParams}.", 33],
            ["Closure parameter '\$data' is not a valid injection for this context. Valid parameters: {$componentParams}.", 37],
            ["Closure parameter '\$records' is not a valid injection for this context. Valid parameters: {$componentParams}.", 41],
            ["Closure parameter '\$get' is not a valid injection for this context. Valid parameters: {$columnParams}.", 50],
            ["Closure parameter '\$set' is not a valid injection for this context. Valid parameters: {$columnParams}.", 54],
            ["Closure parameter '\$operation' is not a valid injection for this context. Valid parameters: {$columnParams}.", 58],
            ["Closure parameter '\$doesnotexist' is not a valid injection for this context. Valid parameters: {$columnNoValueParams}.", 62],
            ["Closure parameter '\$err' is not a valid injection for this context. Valid parameters: {$actionParams}.", 71],
        ],
    );
});

it('does not report errors for valid typed closure injections', function () {
    ConfigurableRuleTestCase::useRule(getEnabledRule());
    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/TypedInjectionValid.php'],
        [],
    );
});

it('reports errors for typed closure injections with wrong types', function () {
    ConfigurableRuleTestCase::useRule(getEnabledRule());
    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/TypedInjectionInvalid.php'],
        [
            ["Closure parameter '\$record' is typed as 'string', expected 'array<string, mixed>|Illuminate\\Database\\Eloquent\\Model|null'.", 16],
            ["Closure parameter '\$record' is typed as 'string', expected 'array<string, mixed>|Illuminate\\Database\\Eloquent\\Model|null'.", 25],
        ],
    );
});

it('reports errors for typed state params with incompatible types', function () {
    ConfigurableRuleTestCase::useRule(getEnabledRule());
    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/TypedStateInjection.php'],
        [
            ["Closure parameter '\$state' is typed as 'array', expected 'string|null'.", 19],
            ["Closure parameter '\$state' is typed as 'array', expected 'string'.", 36],
        ],
    );
});

it('produces no errors when the rule is disabled', function () {
    ConfigurableRuleTestCase::useRule(makeDisabledRule());

    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/InjectionInvalid.php'],
        [],
    );
});