<?php

use ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\Handlers\RecordClosureHandler;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

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
