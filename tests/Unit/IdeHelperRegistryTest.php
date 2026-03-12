<?php

use ImSuperlative\PhpstanFilament\Data\IdeHelperModelData;
use ImSuperlative\PhpstanFilament\Data\IdeHelperPropertyData;
use ImSuperlative\PhpstanFilament\Parser\TypeStringParser;
use ImSuperlative\PhpstanFilament\Support\IdeHelperModelParser;
use ImSuperlative\PhpstanFilament\Support\IdeHelperRegistry;
use PHPStan\Parser\Parser;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

beforeEach(function () {
    /** @var Parser $parser */
    $parser = PHPStanTestCase::getContainer()->getService('defaultAnalysisParser');

    $typeStringParser = TypeStringParser::make();

    $this->modelParser = new IdeHelperModelParser($parser, $typeStringParser->getLexer(), $typeStringParser->getPhpDocParser());
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
    $customPath = fixture_path('App/Models/_ide_helper_models.php');
    $registry = new IdeHelperRegistry($this->modelParser, true, $customPath, __DIR__);

    $post = $registry->getModelData('Fixtures\App\Models\Post');

    expect($post !== null)->toBeTrue()
        ->and($post->properties['title']->type->describe(VerbosityLevel::precise()))->toBe('string');
});

it('returns null when disabled', function () {
    $customPath = fixture_path('App/Models/_ide_helper_models.php');
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
