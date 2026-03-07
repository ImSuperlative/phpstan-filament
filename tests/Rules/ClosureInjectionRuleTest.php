<?php

use ImSuperlative\FilamentPhpstan\Collectors\CustomComponentRegistry;
use ImSuperlative\FilamentPhpstan\Collectors\SchemaCallSiteRegistry;
use ImSuperlative\FilamentPhpstan\Collectors\TableQueryRegistry;
use ImSuperlative\FilamentPhpstan\Parser\StatePathPrefixVisitor;
use ImSuperlative\FilamentPhpstan\Resolvers\AnnotationReader;
use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\FieldPathResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\FormComponentChainResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\FormComponentStateMap;
use ImSuperlative\FilamentPhpstan\Resolvers\FormComponentTypeNarrower;
use ImSuperlative\FilamentPhpstan\Resolvers\FormOptionsNarrower;
use ImSuperlative\FilamentPhpstan\Resolvers\ResourceModelResolver;
use ImSuperlative\FilamentPhpstan\Resolvers\StateTypeResolver;
use ImSuperlative\FilamentPhpstan\Rules\ClosureInjection\ClosureInjectionRule;
use ImSuperlative\FilamentPhpstan\Rules\ClosureInjection\InjectionMapFactory;
use ImSuperlative\FilamentPhpstan\Rules\ClosureInjection\VendorAstParser;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use ImSuperlative\FilamentPhpstan\Support\ModelReflectionHelper;
use ImSuperlative\FilamentPhpstan\Tests\ConfigurableRuleTestCase;
use PHPStan\Parser\Parser;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

function makeTestDependencies(): array
{
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $filamentClassHelper = new FilamentClassHelper($reflectionProvider);

    $config = new ParserConfig(usedAttributes: []);
    $lexer = new Lexer($config);
    $constExprParser = new ConstExprParser($config);
    $typeParser = new TypeParser($config, $constExprParser);
    $phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);
    $annotationReader = new AnnotationReader($lexer, $typeParser, $phpDocParser);

    $tableQueryRegistry = new TableQueryRegistry;
    $componentStateMap = new FormComponentStateMap($reflectionProvider);
    $stateNarrower = new FormComponentTypeNarrower($reflectionProvider, new FormOptionsNarrower);

    $modelReflectionHelper = new ModelReflectionHelper($reflectionProvider);

    /** @var Parser $parser */
    $parser = PHPStanTestCase::getContainer()->getService('defaultAnalysisParser');
    $statePathPrefixVisitor = new StatePathPrefixVisitor($parser);

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
        $statePathPrefixVisitor,
        $fieldPathResolver,
        makeFieldValidation: 3,
    );

    $componentContextResolver = new ComponentContextResolver(
        $filamentClassHelper,
        new ResourceModelResolver($reflectionProvider, $filamentClassHelper),
        $annotationReader,
        $tableQueryRegistry,
        $reflectionProvider,
        $modelReflectionHelper,
        new CustomComponentRegistry,
        new SchemaCallSiteRegistry,
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
    $factory = new InjectionMapFactory($deps['reflectionProvider'], new VendorAstParser);

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

beforeEach(function () {
    ConfigurableRuleTestCase::useRule(makeEnabledRule());
});

it('does not report errors for valid closure injections', function () {
    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/InjectionValid.php'],
        [],
    );
});

it('reports errors for all invalid closure injections', function () {
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
    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/TypedInjectionValid.php'],
        [],
    );
});

it('reports errors for typed closure injections with wrong types', function () {
    $this->analyse(
        [__DIR__.'/../Fixtures/App/ClosureTests/TypedInjectionInvalid.php'],
        [
            ["Closure parameter '\$record' is typed as 'string', expected 'array<string, mixed>|Illuminate\\Database\\Eloquent\\Model|null'.", 16],
            ["Closure parameter '\$record' is typed as 'string', expected 'array<string, mixed>|Illuminate\\Database\\Eloquent\\Model|null'.", 25],
        ],
    );
});

it('reports errors for typed state params with incompatible types', function () {
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
    $factory = new InjectionMapFactory($deps['reflectionProvider'], new VendorAstParser);

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
