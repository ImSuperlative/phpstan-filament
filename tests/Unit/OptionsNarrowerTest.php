<?php

use ImSuperlative\FilamentPhpstan\Resolvers\FormOptionsNarrower;
use ImSuperlative\FilamentPhpstan\Tests\TypeInferenceTestCase;
use PHPStan\Type\VerbosityLevel;

describe('enum narrowing', function () {
    it('narrows single-select with enum to ?EnumClass', function () {
        // Bootstrap PHPStan container so ReflectionProviderStaticAccessor is available
        TypeInferenceTestCase::getContainer();

        $result = new FormOptionsNarrower()->fromEnum(
            enumClass: 'Fixtures\App\Enums\PostStatus',
            isMultiple: false,
        );
        expect($result?->describe(VerbosityLevel::precise()))->toBe('Fixtures\App\Enums\PostStatus|null');
    });

    it('narrows multi-select with enum to array<EnumClass>', function () {
        $result = new FormOptionsNarrower()->fromEnum(
            enumClass: 'Fixtures\App\Enums\PostStatus',
            isMultiple: true,
        );
        expect($result?->describe(VerbosityLevel::precise()))->toBe('array<int, Fixtures\App\Enums\PostStatus>');
    });

    it('returns null for non-existent enum class', function () {
        $result = (new FormOptionsNarrower)->fromEnum(
            enumClass: 'App\Nonexistent\Enum',
            isMultiple: false,
        );
        expect($result)->toBeNull();
    });

    it('returns null for non-backed enum', function () {
        $result = (new FormOptionsNarrower)->fromEnum(
            enumClass: 'Fixtures\App\Enums\UnitEnumExample',
            isMultiple: false,
        );
        expect($result)->toBeNull();
    });
});

describe('literal options narrowing', function () {
    it('narrows single-select with string array keys', function () {
        $result = (new FormOptionsNarrower)->fromLiteralOptions(
            keys: ['draft', 'published', 'archived'],
            isMultiple: false,
        );
        expect($result?->describe(VerbosityLevel::precise()))->toBe("'archived'|'draft'|'published'|null");
    });

    it('narrows multi-select with string array keys', function () {
        $result = (new FormOptionsNarrower)->fromLiteralOptions(
            keys: ['draft', 'published', 'archived'],
            isMultiple: true,
        );
        expect($result?->describe(VerbosityLevel::precise()))->toBe("array<int, 'archived'|'draft'|'published'>");
    });

    it('narrows with integer keys', function () {
        $result = (new FormOptionsNarrower)->fromLiteralOptions(
            keys: [1, 2, 3],
            isMultiple: false,
        );
        expect($result?->describe(VerbosityLevel::precise()))->toBe('1|2|3|null');
    });

    it('narrows with mixed string and int keys', function () {
        $result = (new FormOptionsNarrower)->fromLiteralOptions(
            keys: ['active', 1, 'inactive', 0],
            isMultiple: false,
        );
        expect($result?->describe(VerbosityLevel::precise()))->toBe("0|1|'active'|'inactive'|null");
    });

    it('returns null for empty keys', function () {
        $result = (new FormOptionsNarrower)->fromLiteralOptions(
            keys: [],
            isMultiple: false,
        );
        expect($result)->toBeNull();
    });
});

describe('enum value literals', function () {
    it('resolves backed enum string values as literal union', function () {
        $result = (new FormOptionsNarrower)->enumValuesAsLiteralUnion(
            enumClass: 'Fixtures\App\Enums\PostStatus',
            isMultiple: false,
        );
        expect($result?->describe(VerbosityLevel::precise()))->toBe("'archived'|'draft'|'published'|null");
    });

    it('resolves backed enum string values as array for multi-select', function () {
        $result = (new FormOptionsNarrower)->enumValuesAsLiteralUnion(
            enumClass: 'Fixtures\App\Enums\PostStatus',
            isMultiple: true,
        );
        expect($result?->describe(VerbosityLevel::precise()))->toBe("array<int, 'archived'|'draft'|'published'>");
    });
});
