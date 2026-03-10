<?php

// tests/Unit/PhpDocAnnotationParserTest.php

use ImSuperlative\PhpstanFilament\Data\FilamentModelAnnotation;
use ImSuperlative\PhpstanFilament\Parser\TypeStringParser;
use ImSuperlative\PhpstanFilament\Resolvers\PhpDocAnnotationParser;

beforeEach(function () {
    $typeStringParser = TypeStringParser::make();

    $this->reader = new PhpDocAnnotationParser($typeStringParser);
});

it('parses @filament-model from a PHPDoc string', function () {
    $phpDoc = '/** @filament-model App\Models\Form */';

    $result = $this->reader->readModelAnnotation($phpDoc);
    expect($result)->toBeInstanceOf(FilamentModelAnnotation::class)
        ->and($result->typeAsString())->toBe('App\Models\Form');
});

it('returns null when no @filament-model annotation', function () {
    $phpDoc = '/** @return void */';

    expect($this->reader->readModelAnnotation($phpDoc))
        ->toBeNull();
});

it('parses @filament-state without field name', function () {
    $phpDoc = '/** @filament-state \Carbon\CarbonInterface */';

    $states = $this->reader->readStateAnnotations($phpDoc);

    expect($states)->toHaveCount(1)
        ->and($states[0])->typeAsString()->toBe('\Carbon\CarbonInterface')
        ->and($states[0])->fieldName->toBeNull();
});

it('parses @filament-state with field name', function () {
    $phpDoc = '/** @filament-state \Carbon\CarbonInterface updated_at */';

    $states = $this->reader->readStateAnnotations($phpDoc);

    expect($states)->toHaveCount(1)
        ->and($states[0])->typeAsString()->toBe('\Carbon\CarbonInterface')
        ->and($states[0])->fieldName->toBe('updated_at');
});

it('parses multiple @filament-state annotations', function () {
    $phpDoc = <<<'DOC'
    /**
     * @filament-state \Carbon\CarbonInterface updated_at
     * @filament-state \Carbon\CarbonInterface created_at
     */
    DOC;

    $states = $this->reader->readStateAnnotations($phpDoc);

    expect($states)->toHaveCount(2)
        ->and($states[0])->fieldName->toBe('updated_at')
        ->and($states[1])->fieldName->toBe('created_at');
});

it('reads @filament-field annotations with type and field name', function () {
    $result = $this->reader->readFieldAnnotations('/** @filament-field User causer */');
    expect($result)->toHaveCount(1)
        ->and($result[0])->typeAsString()->toBe('User')
        ->and($result[0])->fieldName->toBe('causer');
});

it('reads multiple @filament-field annotations', function () {
    $result = $this->reader->readFieldAnnotations(<<<'DOC'
    /**
     * @filament-field Email latestSubmissionEmail
     * @filament-field Email latestReminderEmail
     */
    DOC);
    expect($result)->toHaveCount(2)
        ->and($result[0])->typeAsString()->toBe('Email')
        ->and($result[0])->fieldName->toBe('latestSubmissionEmail')
        ->and($result[1])->typeAsString()->toBe('Email')
        ->and($result[1])->fieldName->toBe('latestReminderEmail');
});

it('returns null fieldName for @filament-field without field name', function () {
    $result = $this->reader->readFieldAnnotations('/** @filament-field User */');
    expect($result)->toHaveCount(1)
        ->and($result[0])->typeAsString()->toBe('User')
        ->and($result[0])->fieldName->toBeNull();
});

it('returns empty array when no @filament-field annotations', function () {
    $result = $this->reader->readFieldAnnotations('/** @filament-model Post */');
    expect($result)->toBe([]);
});

it('parses @filament-page with generic model syntax', function () {
    $phpDoc = '/** @filament-page EditPost<Post> */';

    $annotations = $this->reader->readPageAnnotations($phpDoc);

    expect($annotations)->toHaveCount(1);

    $annotation = $annotations[0];
    expect((string) $annotation->pageType())->toBe('EditPost')
        ->and($annotation->modelType())->not->toBeNull()
        ->and((string) $annotation->modelType())->toBe('Post');
});

it('parses @filament-page without generic as page-only', function () {
    $phpDoc = '/** @filament-page EditPost */';

    $annotations = $this->reader->readPageAnnotations($phpDoc);

    expect($annotations)->toHaveCount(1);

    $annotation = $annotations[0];
    expect((string) $annotation->pageType())->toBe('EditPost')
        ->and($annotation->modelType())->toBeNull();
});

it('parses @filament-page union without generic', function () {
    $phpDoc = '/** @filament-page EditPost|CreatePost */';

    $annotations = $this->reader->readPageAnnotations($phpDoc);

    expect($annotations)->toHaveCount(1);

    $annotation = $annotations[0];
    expect($annotation->pageTypes())->toHaveCount(2)
        ->and((string) $annotation->pageTypes()[0])->toBe('EditPost')
        ->and((string) $annotation->pageTypes()[1])->toBe('CreatePost')
        ->and($annotation->modelType())->toBeNull();
});

it('parses multiple @filament-page tags with generics', function () {
    $phpDoc = <<<'DOC'
    /**
     * @filament-page EditPost<Post>
     * @filament-page CreatePost<Post>
     */
    DOC;

    $annotations = $this->reader->readPageAnnotations($phpDoc);

    expect($annotations)->toHaveCount(2)
        ->and((string) $annotations[0]->pageType())->toBe('EditPost')
        ->and((string) $annotations[0]->modelType())->toBe('Post')
        ->and((string) $annotations[1]->pageType())->toBe('CreatePost')
        ->and((string) $annotations[1]->modelType())->toBe('Post');
});
