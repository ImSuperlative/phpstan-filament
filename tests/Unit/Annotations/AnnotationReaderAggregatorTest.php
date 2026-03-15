<?php

use ImSuperlative\PhpstanFilament\Data\FilamentModelAnnotation;
use ImSuperlative\PhpstanFilament\Parser\TypeStringParser;
use ImSuperlative\PhpstanFilament\Resolvers\AnnotationReader;
use ImSuperlative\PhpstanFilament\Resolvers\AttributeAnnotationParser;
use ImSuperlative\PhpstanFilament\Resolvers\PhpDocAnnotationParser;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

beforeEach(function () {
    $typeStringParser = TypeStringParser::make();

    $this->reader = new AnnotationReader(
        new AttributeAnnotationParser($typeStringParser),
        new PhpDocAnnotationParser($typeStringParser),
    );
});

function aggregatorClassReflection(string $className): \PHPStan\Reflection\ClassReflection
{
    return PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class)
        ->getClass($className);
}

it('reads model from attribute', function () {
    $result = $this->reader->readModelAnnotation(aggregatorClassReflection('Fixtures\App\Attributes\ModelOnly'));
    expect($result)->toBeInstanceOf(FilamentModelAnnotation::class)
        ->and($result->typeAsString())->toBe('Fixtures\App\Models\Post');
});

it('reads model from PHPDoc when no attribute', function () {
    $result = $this->reader->readModelAnnotation(aggregatorClassReflection('Fixtures\App\Attributes\PhpDocOnly'));
    expect($result)->toBeInstanceOf(FilamentModelAnnotation::class)
        ->and($result->typeAsString())->toBe('Fixtures\App\Models\Post');
});

it('attribute takes precedence over PHPDoc for model', function () {
    $result = $this->reader->readModelAnnotation(aggregatorClassReflection('Fixtures\App\Attributes\MixedAnnotations'));
    expect($result)->toBeInstanceOf(FilamentModelAnnotation::class)
        ->and($result->typeAsString())->toBe('Fixtures\App\Models\Post');
});

it('returns null when neither attribute nor PHPDoc present', function () {
    $result = $this->reader->readModelAnnotation(aggregatorClassReflection('Fixtures\App\Attributes\NoAttributes'));
    expect($result)->toBeNull();
});

it('returns empty page annotations when none present', function () {
    $result = $this->reader->readPageAnnotations(aggregatorClassReflection('Fixtures\App\Attributes\NoAttributes'));
    expect($result)->toBe([]);
});

it('reads page annotations from attributes', function () {
    $result = $this->reader->readPageAnnotations(aggregatorClassReflection('Fixtures\App\Attributes\MultiplePages'));
    expect($result)->toHaveCount(2);
});

it('reads state annotations from attributes', function () {
    $result = $this->reader->readStateAnnotations(aggregatorClassReflection('Fixtures\App\Attributes\StateAnnotated'));
    expect($result)->toHaveCount(2);
});

it('reads field annotations from attributes', function () {
    $result = $this->reader->readFieldAnnotations(aggregatorClassReflection('Fixtures\App\Attributes\FieldAnnotated'));
    expect($result)->toHaveCount(1);
});

it('supports method-level annotations', function () {
    $result = $this->reader->readModelAnnotation(
        aggregatorClassReflection('Fixtures\App\Attributes\MethodLevelAnnotation'),
        'configure',
    );
    expect($result)->toBeInstanceOf(FilamentModelAnnotation::class)
        ->and($result->typeAsString())->toBe('Fixtures\App\Models\Post');
});
