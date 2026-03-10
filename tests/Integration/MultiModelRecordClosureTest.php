<?php

use ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\Handlers\RecordClosureHandler;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;

/**
 * Build a ClosureHandlerContext for unit testing handlers.
 *
 * @param  list<string>  $modelClasses
 */
function buildHandlerContext(
    array $modelClasses = [],
    ?string $callerClass = null,
    ?string $declaringClass = null,
): ClosureHandlerContext {
    $scopeFactory = PHPStanTestCase::getContainer()->getByType(ScopeFactory::class);
    $scope = $scopeFactory->create(ScopeContext::create('test.php'));

    $methodCall = new MethodCall(
        new Variable('this'),
        new Identifier('test'),
    );

    return new ClosureHandlerContext(
        scope: $scope,
        methodCall: $methodCall,
        modelClasses: $modelClasses,
        callerClass: $callerClass,
        declaringClass: $declaringClass,
    );
}

function createRecordHandler(): RecordClosureHandler
{
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);

    return new RecordClosureHandler(
        recordClosure: true,
        filamentClassHelper: new FilamentClassHelper($reflectionProvider),
    );
}

it('builds a union type from multiple model classes', function () {
    $handler = createRecordHandler();
    $result = $handler->resolveType('record', false, buildHandlerContext(
        modelClasses: ['Fixtures\App\Models\Post', 'Fixtures\App\Models\Comment'],
    ), null);

    $actual = $result?->describe(VerbosityLevel::precise());
    unset($handler, $result);

    $expected = TypeCombinator::addNull(
        TypeCombinator::union(
            new ObjectType('Fixtures\App\Models\Post'),
            new ObjectType('Fixtures\App\Models\Comment'),
        ),
    );

    expect($actual)->toBe($expected->describe(VerbosityLevel::precise()));
});

it('builds a non-nullable union for table column context', function () {
    $handler = createRecordHandler();
    $result = $handler->resolveType('record', false, buildHandlerContext(
        modelClasses: ['Fixtures\App\Models\Post', 'Fixtures\App\Models\Comment'],
        callerClass: 'Filament\Tables\Columns\TextColumn',
    ), null);

    $actual = $result?->describe(VerbosityLevel::precise());
    unset($handler, $result);

    $expected = TypeCombinator::union(
        new ObjectType('Fixtures\App\Models\Post'),
        new ObjectType('Fixtures\App\Models\Comment'),
    );

    expect($actual)->toBe($expected->describe(VerbosityLevel::precise()));
});

it('returns null when modelClasses is empty', function () {
    $handler = createRecordHandler();
    $result = $handler->resolveType('record', false, buildHandlerContext(modelClasses: []), null);
    unset($handler);

    expect($result)->toBeNull();
});
