<?php

use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\Handlers\RecordClosureHandler;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

beforeEach(function () {
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $this->handler = new RecordClosureHandler(
        recordClosure: true,
        filamentClassHelper: new FilamentClassHelper($reflectionProvider),
    );
});

it('builds a union type from multiple model classes', function () {
    $context = buildHandlerContext(
        modelClasses: ['Fixtures\App\Models\Post', 'Fixtures\App\Models\Comment'],
    );

    $result = $this->handler->resolveType('record', false, $context, null);

    expect($result)->not->toBeNull();

    $expected = TypeCombinator::addNull(
        TypeCombinator::union(
            new ObjectType('Fixtures\App\Models\Post'),
            new ObjectType('Fixtures\App\Models\Comment'),
        ),
    );

    expect($result->describe(VerbosityLevel::precise()))
        ->toBe($expected->describe(VerbosityLevel::precise()));
});

it('builds a non-nullable union for table column context', function () {
    $context = buildHandlerContext(
        modelClasses: ['Fixtures\App\Models\Post', 'Fixtures\App\Models\Comment'],
        callerClass: 'Filament\Tables\Columns\TextColumn',
    );

    $result = $this->handler->resolveType('record', false, $context, null);

    expect($result)->not->toBeNull();

    $expected = TypeCombinator::union(
        new ObjectType('Fixtures\App\Models\Post'),
        new ObjectType('Fixtures\App\Models\Comment'),
    );

    expect($result->describe(VerbosityLevel::precise()))
        ->toBe($expected->describe(VerbosityLevel::precise()));
});

it('returns null when modelClasses is empty', function () {
    $context = buildHandlerContext(modelClasses: []);

    $result = $this->handler->resolveType('record', false, $context, null);

    expect($result)->toBeNull();
});
