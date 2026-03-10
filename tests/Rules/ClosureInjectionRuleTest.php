<?php

use ImSuperlative\PhpstanFilament\Parser\TypeStringParser;
use ImSuperlative\PhpstanFilament\Resolvers\AnnotationReader;
use ImSuperlative\PhpstanFilament\Resolvers\AttributeAnnotationParser;
use ImSuperlative\PhpstanFilament\Resolvers\ComponentContextResolver;
use ImSuperlative\PhpstanFilament\Resolvers\FieldPathResolver;
use ImSuperlative\PhpstanFilament\Resolvers\FormComponentChainResolver;
use ImSuperlative\PhpstanFilament\Resolvers\FormComponentStateMap;
use ImSuperlative\PhpstanFilament\Resolvers\FormComponentTypeNarrower;
use ImSuperlative\PhpstanFilament\Resolvers\FormOptionsNarrower;
use ImSuperlative\PhpstanFilament\Resolvers\PhpDocAnnotationParser;
use ImSuperlative\PhpstanFilament\Resolvers\ResourceModelResolver;
use ImSuperlative\PhpstanFilament\Resolvers\StateTypeResolver;
use ImSuperlative\PhpstanFilament\Resolvers\VirtualAnnotationProvider;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\DiscoveredClassCache;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\ClosureInjectionRule;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\InjectionMapFactory;
use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\VendorAstParser;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use ImSuperlative\PhpstanFilament\Support\ModelReflectionHelper;
use ImSuperlative\PhpstanFilament\Tests\ConfigurableRuleTestCase;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

function makeTestDependencies(): array
{
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $filamentClassHelper = new FilamentClassHelper($reflectionProvider);

    $typeStringParser = TypeStringParser::make();
    $annotationReader = new AnnotationReader(
        new AttributeAnnotationParser($typeStringParser),
        new PhpDocAnnotationParser($typeStringParser),
    );

    $componentStateMap = new FormComponentStateMap($reflectionProvider);
    $stateNarrower = new FormComponentTypeNarrower($reflectionProvider, new FormOptionsNarrower);

    $modelReflectionHelper = new ModelReflectionHelper($reflectionProvider);

    $fieldPathResolver = new FieldPathResolver(
        $modelReflectionHelper,
        $reflectionProvider,
    );

    $chainResolver = new FormComponentChainResolver;

    $stateTypeResolver = new StateTypeResolver(
        $componentStateMap,
        $stateNarrower,
        $chainResolver,
        $reflectionProvider,
        $filamentClassHelper,
        $fieldPathResolver,
        makeFieldValidation: 3,
    );

    $resourceModelResolver = new ResourceModelResolver($reflectionProvider, $filamentClassHelper, $modelReflectionHelper);

    $componentContextResolver = new ComponentContextResolver(
        $filamentClassHelper,
        $resourceModelResolver,
        $annotationReader,
        $reflectionProvider,
        $modelReflectionHelper,
        new VirtualAnnotationProvider(
            enabled: false,
            filamentPath: [],
            currentWorkingDirectory: '',
            analysedPaths: [],
            analysedPathsFromConfig: [],
            resourceModelResolver: $resourceModelResolver,
        ),
    );

    return [
        'reflectionProvider' => $reflectionProvider,
        'filamentClassHelper' => $filamentClassHelper,
        'stateTypeResolver' => $stateTypeResolver,
        'componentContextResolver' => $componentContextResolver,
    ];
}

function makeEnabledRule(): ClosureInjectionRule
{
    $deps = makeTestDependencies();
    $factory = new InjectionMapFactory($deps['reflectionProvider'], new VendorAstParser, new DiscoveredClassCache);

    return new ClosureInjectionRule(
        closureInjection: true,
        reservedClosureInjection: false,
        injectionMap: $factory->create(),
        filamentClassHelper: $deps['filamentClassHelper'],
        reflectionProvider: $deps['reflectionProvider'],
        stateTypeResolver: $deps['stateTypeResolver'],
        componentContextResolver: $deps['componentContextResolver'],
    );
}

it('does not report errors for valid closure injections', function () {
    ConfigurableRuleTestCase::useRule(makeEnabledRule());
    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/InjectionValid.php'],
        [],
    );
});

it('reports errors for all invalid closure injections', function () {
    ConfigurableRuleTestCase::useRule(makeEnabledRule());
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
    ConfigurableRuleTestCase::useRule(makeEnabledRule());
    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/TypedInjectionValid.php'],
        [],
    );
});

it('reports errors for typed closure injections with wrong types', function () {
    ConfigurableRuleTestCase::useRule(makeEnabledRule());
    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/TypedInjectionInvalid.php'],
        [
            ["Closure parameter '\$record' is typed as 'string', expected 'array<string, mixed>|Illuminate\\Database\\Eloquent\\Model|null'.", 16],
            ["Closure parameter '\$record' is typed as 'string', expected 'array<string, mixed>|Illuminate\\Database\\Eloquent\\Model|null'.", 25],
        ],
    );
});

it('reports errors for typed state params with incompatible types', function () {
    ConfigurableRuleTestCase::useRule(makeEnabledRule());
    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/TypedStateInjection.php'],
        [
            ["Closure parameter '\$state' is typed as 'array', expected 'string|null'.", 19],
            ["Closure parameter '\$state' is typed as 'array', expected 'string'.", 36],
            ["Closure parameter '\$state' is typed as 'array', expected 'string'.", 52],
        ],
    );
});

it('produces no errors when the rule is disabled', function () {
    $deps = makeTestDependencies();
    $factory = new InjectionMapFactory($deps['reflectionProvider'], new VendorAstParser, new DiscoveredClassCache);

    ConfigurableRuleTestCase::useRule(new ClosureInjectionRule(
        closureInjection: false,
        reservedClosureInjection: false,
        injectionMap: $factory->create(),
        filamentClassHelper: $deps['filamentClassHelper'],
        reflectionProvider: $deps['reflectionProvider'],
        stateTypeResolver: $deps['stateTypeResolver'],
        componentContextResolver: $deps['componentContextResolver'],
    ));

    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/InjectionInvalid.php'],
        [],
    );
});
