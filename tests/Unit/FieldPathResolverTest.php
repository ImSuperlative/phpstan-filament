<?php

use ImSuperlative\PhpstanFilament\Data\SegmentTag;
use ImSuperlative\PhpstanFilament\Resolvers\FieldPathResolver;
use ImSuperlative\PhpstanFilament\Support\ModelReflectionHelper;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\StringType;

beforeEach(function () {
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
    $this->resolver = new FieldPathResolver(
        new ModelReflectionHelper($reflectionProvider),
        $reflectionProvider,
    );
    $scopeFactory = PHPStanTestCase::getContainer()->getByType(ScopeFactory::class);
    $this->scope = $scopeFactory->create(ScopeContext::create('test.php'));

    // Mirrors Analyser::collectErrors() — suppress E_DEPRECATED from
    // PHPStan's BetterReflection parsing deprecated constants in stubs.
    set_error_handler(static function (int $errno) {
        return $errno === E_DEPRECATED;
    });
});

afterEach(function () {
    restore_error_handler();
});

it('resolves a simple property field', function () {
    $result = $this->resolver->resolve('title', 'Fixtures\App\Models\Post', $this->scope);

    expect($result->isFullyResolved())->toBeTrue()
        ->and($result->segments)->toHaveCount(1)
        ->and($result->segments[0]->is(SegmentTag::Property))->toBeTrue()
        ->and($result->leafType())->toBeInstanceOf(StringType::class);
});

it('resolves a relation as a single segment', function () {
    $result = $this->resolver->resolve('author', 'Fixtures\App\Models\Post', $this->scope);

    expect($result->isFullyResolved())->toBeTrue()
        ->and($result->segments[0]->is(SegmentTag::Relation))->toBeTrue()
        ->and($result->segments[0]->is(SegmentTag::Method))->toBeTrue();
});

it('resolves dot-notation through relation', function () {
    $result = $this->resolver->resolve('author.name', 'Fixtures\App\Models\Post', $this->scope);

    expect($result->isFullyResolved())->toBeTrue()
        ->and($result->segments[0]->is(SegmentTag::Relation))->toBeTrue()
        ->and($result->segments[0]->resolvedClass)->toBe('Fixtures\App\Models\Author')
        ->and($result->segments[1]->is(SegmentTag::Property))->toBeTrue();
});

it('resolves typed property in dot-notation', function () {
    $result = $this->resolver->resolve('options.meta', 'Fixtures\App\Models\Post', $this->scope);

    expect($result->segments[0]->is(SegmentTag::Property))->toBeTrue()
        ->and($result->segments[0]->is(SegmentTag::TypedProperty))->toBeTrue()
        ->and($result->segments[0]->resolvedClass)->toBe('Fixtures\App\Data\PostOptions');
});

it('tags method that is not a relation', function () {
    $result = $this->resolver->resolve('getFullTitle', 'Fixtures\App\Models\Post', $this->scope);

    expect($result->segments[0]->is(SegmentTag::Method))->toBeTrue()
        ->and($result->segments[0]->is(SegmentTag::Relation))->toBeFalse();
});

it('stops at unresolvable segment with remaining', function () {
    $result = $this->resolver->resolve('nonexistent.foo.bar', 'Fixtures\App\Models\Post', $this->scope);

    expect($result->isFullyResolved())->toBeFalse()
        ->and($result->segments)->toHaveCount(1)
        ->and($result->segments[0]->tags)->toBe([])
        ->and($result->remaining)->toBe(['foo', 'bar']);
});

it('handles unknown model class', function () {
    $result = $this->resolver->resolve('title', 'NonExistent\Model', $this->scope);

    expect($result->segments)->toHaveCount(1)
        ->and($result->segments[0]->tags)->toBe([]);
});
