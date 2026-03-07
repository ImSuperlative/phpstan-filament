<?php

// tests/Unit/AnnotationReaderTest.php

use ImSuperlative\FilamentPhpstan\Data\FilamentModelAnnotation;
use ImSuperlative\FilamentPhpstan\Resolvers\AnnotationReader;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

beforeEach(function () {
    $config = new ParserConfig(usedAttributes: []);
    $lexer = new Lexer($config);
    $constExprParser = new ConstExprParser($config);
    $typeParser = new TypeParser($config, $constExprParser);
    $phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);

    $this->reader = new AnnotationReader($lexer, $typeParser, $phpDocParser);
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
