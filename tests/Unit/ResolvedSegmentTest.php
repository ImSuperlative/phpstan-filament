<?php

use ImSuperlative\FilamentPhpstan\Data\ResolvedSegment;
use ImSuperlative\FilamentPhpstan\Data\SegmentTag;
use PHPStan\Type\StringType;

it('checks single tag', function () {
    $segment = new ResolvedSegment('author', [SegmentTag::Relation, SegmentTag::Property], 'Author', null);

    expect($segment->is(SegmentTag::Relation))->toBeTrue()
        ->and($segment->is(SegmentTag::Method))->toBeFalse();
});

it('checks any of multiple tags', function () {
    $segment = new ResolvedSegment('data', [SegmentTag::Property, SegmentTag::TypedProperty], 'AuthorData', null);

    expect($segment->isAny(SegmentTag::Relation, SegmentTag::Property))->toBeTrue()
        ->and($segment->isAny(SegmentTag::Relation, SegmentTag::Method))->toBeFalse();
});

it('identifies unresolved segment by empty tags', function () {
    $segment = new ResolvedSegment('nonexistent', [], null, null);

    expect($segment->is(SegmentTag::Relation))->toBeFalse()
        ->and($segment->isAny(SegmentTag::Relation, SegmentTag::Property, SegmentTag::Method))->toBeFalse()
        ->and($segment->resolvedClass)->toBeNull();
});

it('carries leaf type', function () {
    $type = new StringType;
    $segment = new ResolvedSegment('title', [SegmentTag::Property], null, $type);

    expect($segment->type)->toBe($type)
        ->and($segment->is(SegmentTag::Property))->toBeTrue();
});
