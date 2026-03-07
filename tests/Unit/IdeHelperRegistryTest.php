<?php

use ImSuperlative\FilamentPhpstan\Data\IdeHelperModelData;
use ImSuperlative\FilamentPhpstan\Data\IdeHelperPropertyData;
use ImSuperlative\FilamentPhpstan\Support\IdeHelperModelParser;
use ImSuperlative\FilamentPhpstan\Support\IdeHelperRegistry;
use PHPStan\Parser\Parser;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

beforeEach(function () {
    /** @var Parser $parser */
    $parser = PHPStanTestCase::getContainer()->getService('defaultAnalysisParser');

    $config = new ParserConfig(usedAttributes: []);
    $lexer = new Lexer($config);
    $constExprParser = new ConstExprParser($config);
    $typeParser = new TypeParser($config, $constExprParser);
    $phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);

    $this->modelParser = new IdeHelperModelParser($parser, $lexer, $phpDocParser);
});

it('returns null for unknown class', function () {
    $registry = new IdeHelperRegistry($this->modelParser, true, '', __DIR__);

    expect($registry->getModelData('NonExistent\Class'))->toBeNull();
});

it('can register data manually and retrieve it', function () {
    $registry = new IdeHelperRegistry($this->modelParser, true, '', __DIR__);

    $data = new IdeHelperModelData('App\Models\Foo', [
        'bar' => new IdeHelperPropertyData('bar', new StringType),
    ]);

    $registry->register($data);

    $result = $registry->getModelData('App\Models\Foo');
    expect($result !== null)->toBeTrue()
        ->and($result->className)->toBe('App\Models\Foo')
        ->and($result->properties['bar']->type->describe(VerbosityLevel::precise()))->toBe('string');
});

it('parses fixture files via custom path', function () {
    $customPath = __DIR__.'/../Fixtures/App/Models/_ide_helper_models.php';
    $registry = new IdeHelperRegistry($this->modelParser, true, $customPath, __DIR__);

    $post = $registry->getModelData('Fixtures\App\Models\Post');

    expect($post !== null)->toBeTrue()
        ->and($post->properties['title']->type->describe(VerbosityLevel::precise()))->toBe('string');
});

it('returns null when disabled', function () {
    $customPath = __DIR__.'/../Fixtures/App/Models/_ide_helper_models.php';
    $registry = new IdeHelperRegistry($this->modelParser, false, $customPath, __DIR__);

    expect($registry->getModelData('Fixtures\App\Models\Post'))->toBeNull();
});

it('Laravel Idea takes priority over barryvdh per-class', function () {
    $registry = new IdeHelperRegistry($this->modelParser, true, '', __DIR__);

    // Simulate barryvdh data (lower priority)
    $barryvdhData = new IdeHelperModelData('App\Models\User', [
        'name' => new IdeHelperPropertyData('name', new StringType),
    ]);
    $registry->register($barryvdhData);

    // Simulate Laravel Idea data (higher priority) - overwrites
    $nullableString = TypeCombinator::addNull(new StringType);
    $ideaData = new IdeHelperModelData('App\Models\User', [
        'name' => new IdeHelperPropertyData('name', $nullableString),
        'email' => new IdeHelperPropertyData('email', new StringType),
    ]);
    $registry->register($ideaData);

    $result = $registry->getModelData('App\Models\User');
    expect($result->properties['name']->type->describe(VerbosityLevel::precise()))->toBe('string|null')
        ->and($result->properties)->toHaveKey('email');
});
