<?php

use ImSuperlative\PhpstanFilament\Data\ChainAnalysis;
use ImSuperlative\PhpstanFilament\Resolvers\FormComponentTypeNarrower;
use ImSuperlative\PhpstanFilament\Resolvers\FormOptionsNarrower;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

beforeEach(function () {
    $this->narrower = new FormComponentTypeNarrower(
        PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class),
        new FormOptionsNarrower,
    );
});

/** Helper to create nullable string type */
function nullableString(): Type
{
    return TypeCombinator::addNull(new StringType);
}

/** Helper to create string|int|null type */
function stringIntNull(): Type
{
    return TypeCombinator::addNull(TypeCombinator::union(new StringType, new IntegerType));
}

/** Helper to create array<int, int|string> type */
function arrayStringInt(): Type
{
    return new ArrayType(new IntegerType, TypeCombinator::union(new StringType, new IntegerType));
}

it('narrows TextInput with numeric()', function () {
    $result = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\TextInput',
        baseType: nullableString(),
        methodCalls: ['numeric'],
    );
    expect($result->describe(VerbosityLevel::precise()))->toBe('float|null');
});

it('narrows TextInput with integer()', function () {
    $result = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\TextInput',
        baseType: nullableString(),
        methodCalls: ['integer'],
    );
    expect($result->describe(VerbosityLevel::precise()))->toBe('float|null');
});

it('narrows Select with multiple()', function () {
    $result = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\Select',
        baseType: stringIntNull(),
        methodCalls: ['multiple'],
    );
    expect($result->describe(VerbosityLevel::precise()))->toBe('array<int, int|string>');
});

it('narrows FileUpload with multiple()', function () {
    $result = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\FileUpload',
        baseType: nullableString(),
        methodCalls: ['multiple'],
    );
    expect($result->describe(VerbosityLevel::precise()))->toBe('array<int, string>|null');
});

it('narrows Radio with boolean()', function () {
    $result = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\Radio',
        baseType: stringIntNull(),
        methodCalls: ['boolean'],
    );
    expect($result->describe(VerbosityLevel::precise()))->toBe('int|null');
});

it('returns base type when no narrowing methods present', function () {
    $result = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\TextInput',
        baseType: nullableString(),
        methodCalls: ['required', 'maxLength', 'email'],
    );
    expect($result->describe(VerbosityLevel::precise()))->toBe('string|null');
});

it('returns base type for unknown component', function () {
    $result = $this->narrower->narrow(
        componentClass: 'App\Custom\Unknown',
        baseType: nullableString(),
        methodCalls: ['numeric'],
    );
    expect($result->describe(VerbosityLevel::precise()))->toBe('string|null');
});

it('applies last matching rule when multiple narrowing methods called', function () {
    $result = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\TextInput',
        baseType: nullableString(),
        methodCalls: ['numeric', 'integer'],
    );
    expect($result->describe(VerbosityLevel::precise()))->toBe('float|null');
});

it('handles subclass components by walking parents', function () {
    $result = $this->narrower->narrowForClass(
        className: 'Filament\Forms\Components\Select',
        baseType: stringIntNull(),
        methodCalls: ['multiple'],
    );
    expect($result->describe(VerbosityLevel::precise()))->toBe('array<int, int|string>');
});

it('narrows ToggleButtons with multiple()', function () {
    $result = $this->narrower->narrow(
        componentClass: 'Filament\Forms\Components\ToggleButtons',
        baseType: stringIntNull(),
        methodCalls: ['multiple'],
    );
    expect($result->describe(VerbosityLevel::precise()))->toBe('array<int, int|string>');
});

describe('options and enum narrowing', function () {
    it('narrows Select with enum class', function () {
        $analysis = new ChainAnalysis(
            componentClass: 'Filament\Forms\Components\Select',
            methodCalls: ['enum'],
            enumClass: 'Fixtures\App\Enums\PostStatus',
            literalOptionKeys: null,
            isMultiple: false,
            isRequired: false,
            fieldName: null,
        );
        $result = $this->narrower->narrowWithOptions($analysis, stringIntNull());
        expect($result->describe(VerbosityLevel::precise()))->toBe('Fixtures\App\Enums\PostStatus|null');
    });

    it('narrows Select with enum class and multiple()', function () {
        $analysis = new ChainAnalysis(
            componentClass: 'Filament\Forms\Components\Select',
            methodCalls: ['enum', 'multiple'],
            enumClass: 'Fixtures\App\Enums\PostStatus',
            literalOptionKeys: null,
            isMultiple: true,
            isRequired: false,
            fieldName: null,
        );
        $result = $this->narrower->narrowWithOptions($analysis, stringIntNull());
        expect($result->describe(VerbosityLevel::precise()))->toBe('array<int, Fixtures\App\Enums\PostStatus>');
    });

    it('narrows Select with literal options array', function () {
        $analysis = new ChainAnalysis(
            componentClass: 'Filament\Forms\Components\Select',
            methodCalls: ['options'],
            enumClass: null,
            literalOptionKeys: ['draft', 'published', 'archived'],
            isMultiple: false,
            isRequired: false,
            fieldName: null,
        );
        $result = $this->narrower->narrowWithOptions($analysis, stringIntNull());
        expect($result->describe(VerbosityLevel::precise()))->toBe("'archived'|'draft'|'published'|null");
    });

    it('narrows CheckboxList with enum (always multi)', function () {
        $analysis = new ChainAnalysis(
            componentClass: 'Filament\Forms\Components\CheckboxList',
            methodCalls: ['enum'],
            enumClass: 'Fixtures\App\Enums\PostStatus',
            literalOptionKeys: null,
            isMultiple: true,
            isRequired: false,
            fieldName: null,
        );
        $result = $this->narrower->narrowWithOptions($analysis, arrayStringInt());
        expect($result->describe(VerbosityLevel::precise()))->toBe('array<int, Fixtures\App\Enums\PostStatus>');
    });

    it('falls back to base narrowing when options not resolvable', function () {
        $analysis = new ChainAnalysis(
            componentClass: 'Filament\Forms\Components\Select',
            methodCalls: ['multiple'],
            enumClass: null,
            literalOptionKeys: null,
            isMultiple: true,
            isRequired: false,
            fieldName: null,
        );
        $result = $this->narrower->narrowWithOptions($analysis, stringIntNull());
        expect($result->describe(VerbosityLevel::precise()))->toBe('array<int, int|string>');
    });
});
