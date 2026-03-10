<?php

use ImSuperlative\PhpstanFilament\Data\FieldPathResult;
use ImSuperlative\PhpstanFilament\Data\ResolvedSegment;
use ImSuperlative\PhpstanFilament\Data\SegmentTag;
use PHPStan\Type\StringType;

it('reports fully resolved path', function () {
    $result = new FieldPathResult('Post', [
        new ResolvedSegment('author', [SegmentTag::Relation], 'Author', null),
        new ResolvedSegment('name', [SegmentTag::Property], null, new StringType),
    ], []);

    expect($result->isFullyResolved())->toBeTrue()
        ->and($result->leafType())->toBeInstanceOf(StringType::class)
        ->and($result->lastResolvedClass())->toBe('Author')
        ->and($result->firstUnresolved())->toBeNull();
});

it('reports partially resolved path', function () {
    $result = new FieldPathResult('Post', [
        new ResolvedSegment('author', [SegmentTag::Relation], 'Author', null),
        new ResolvedSegment('nonexistent', [], null, null),
    ], ['foo', 'bar']);

    expect($result->isFullyResolved())->toBeFalse()
        ->and($result->leafType())->toBeNull()
        ->and($result->lastResolvedClass())->toBe('Author')
        ->and($result->firstUnresolved()->name)->toBe('nonexistent')
        ->and($result->remaining)->toBe(['foo', 'bar']);
});

it('handles single segment plain field', function () {
    $result = new FieldPathResult('Post', [
        new ResolvedSegment('title', [SegmentTag::Property], null, new StringType),
    ], []);

    expect($result->isFullyResolved())->toBeTrue()
        ->and($result->leafType())->toBeInstanceOf(StringType::class);
});

it('handles completely unresolved path', function () {
    $result = new FieldPathResult('Post', [
        new ResolvedSegment('nonexistent', [], null, null),
    ], ['field']);

    expect($result->isFullyResolved())->toBeFalse()
        ->and($result->firstUnresolved()->name)->toBe('nonexistent');
});
