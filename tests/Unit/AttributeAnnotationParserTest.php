<?php

use ImSuperlative\PhpstanFilament\Data\FilamentFieldAnnotation;
use ImSuperlative\PhpstanFilament\Data\FilamentModelAnnotation;
use ImSuperlative\PhpstanFilament\Data\FilamentPageAnnotation;
use ImSuperlative\PhpstanFilament\Data\FilamentStateAnnotation;
use ImSuperlative\PhpstanFilament\Parser\TypeStringParser;
use ImSuperlative\PhpstanFilament\Resolvers\AttributeAnnotationParser;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

beforeEach(function () {
    $typeStringParser = TypeStringParser::make();

    $this->parser = new AttributeAnnotationParser($typeStringParser);
});

function classReflection(string $className): \PHPStan\Reflection\ClassReflection
{
    return PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class)
        ->getClass($className);
}

it('reads FilamentModel attribute', function () {
    $result = $this->parser->readModelAnnotation(classReflection('Fixtures\App\Attributes\ModelOnly'));
    expect($result)->toBeInstanceOf(FilamentModelAnnotation::class)
        ->and($result->typeAsString())->toBe('Fixtures\App\Models\Post');
});

it('returns null when no FilamentModel attribute', function () {
    $result = $this->parser->readModelAnnotation(classReflection('Fixtures\App\Attributes\NoAttributes'));
    expect($result)->toBeNull();
});

it('reads FilamentPage attribute with model', function () {
    $annotations = $this->parser->readPageAnnotations(classReflection('Fixtures\App\Attributes\PageWithModel'));
    expect($annotations)->toHaveCount(1)
        ->and($annotations[0])->toBeInstanceOf(FilamentPageAnnotation::class)
        ->and((string) $annotations[0]->pageType())->toBe('Fixtures\App\Resources\PostResource\Pages\EditPost')
        ->and((string) $annotations[0]->modelType())->toBe('Fixtures\App\Models\Post');
});

it('reads multiple FilamentPage attributes', function () {
    $annotations = $this->parser->readPageAnnotations(classReflection('Fixtures\App\Attributes\MultiplePages'));
    expect($annotations)->toHaveCount(2)
        ->and((string) $annotations[0]->pageType())->toBe('Fixtures\App\Resources\PostResource\Pages\EditPost')
        ->and((string) $annotations[1]->pageType())->toBe('Fixtures\App\Resources\PostResource\Pages\CreatePost');
});

it('reads FilamentPage with array of types', function () {
    $annotations = $this->parser->readPageAnnotations(classReflection('Fixtures\App\Attributes\PageArray'));
    expect($annotations)->toHaveCount(1)
        ->and($annotations[0]->isUnion())->toBeTrue()
        ->and($annotations[0]->pageTypes())->toHaveCount(2);
});

it('reads multiple FilamentState attributes', function () {
    $annotations = $this->parser->readStateAnnotations(classReflection('Fixtures\App\Attributes\StateAnnotated'));
    expect($annotations)->toHaveCount(2)
        ->and($annotations[0])->toBeInstanceOf(FilamentStateAnnotation::class)
        ->and($annotations[0]->typeAsString())->toBe('Carbon\Carbon')
        ->and($annotations[0]->fieldName)->toBe('updated_at')
        ->and($annotations[1]->fieldName)->toBe('created_at');
});

it('reads FilamentState with type expression containing pipe', function () {
    $annotations = $this->parser->readStateAnnotations(classReflection('Fixtures\App\Attributes\StateWithTypeExpression'));
    expect($annotations)->toHaveCount(1)
        ->and($annotations[0]->isUnion())->toBeTrue()
        ->and($annotations[0]->fieldName)->toBe('deleted_at');
});

it('reads FilamentField attribute', function () {
    $annotations = $this->parser->readFieldAnnotations(classReflection('Fixtures\App\Attributes\FieldAnnotated'));
    expect($annotations)->toHaveCount(1)
        ->and($annotations[0])->toBeInstanceOf(FilamentFieldAnnotation::class)
        ->and($annotations[0]->typeAsString())->toBe('Fixtures\App\Models\Email')
        ->and($annotations[0]->fieldName)->toBe('latestEmail');
});

it('reads method-level FilamentModel attribute', function () {
    $result = $this->parser->readModelAnnotation(
        classReflection('Fixtures\App\Attributes\MethodLevelAnnotation'),
        'configure',
    );
    expect($result)->toBeInstanceOf(FilamentModelAnnotation::class)
        ->and($result->typeAsString())->toBe('Fixtures\App\Models\Post');
});

it('returns empty arrays when no attributes present', function () {
    $class = classReflection('Fixtures\App\Attributes\NoAttributes');
    expect($this->parser->readPageAnnotations($class))->toBe([])
        ->and($this->parser->readStateAnnotations($class))->toBe([])
        ->and($this->parser->readFieldAnnotations($class))->toBe([]);
});
