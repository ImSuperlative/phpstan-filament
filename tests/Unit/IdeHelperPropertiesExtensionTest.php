<?php

use ImSuperlative\FilamentPhpstan\Data\IdeHelperModelData;
use ImSuperlative\FilamentPhpstan\Data\IdeHelperPropertyData;
use ImSuperlative\FilamentPhpstan\Extensions\IdeHelper\IdeHelperPropertiesExtension;
use ImSuperlative\FilamentPhpstan\Support\IdeHelperModelParser;
use ImSuperlative\FilamentPhpstan\Support\IdeHelperRegistry;
use PHPStan\Parser\Parser;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;

beforeEach(function () {
    /** @var Parser $parser */
    $parser = PHPStanTestCase::getContainer()->getService('defaultAnalysisParser');

    $config = new ParserConfig(usedAttributes: []);
    $lexer = new Lexer($config);
    $constExprParser = new ConstExprParser($config);
    $typeParser = new TypeParser($config, $constExprParser);
    $phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);

    $this->modelParser = new IdeHelperModelParser($parser, $lexer, $phpDocParser);
    $customPath = __DIR__.'/../Fixtures/App/Models/_ide_helper_models.php';
    $registry = new IdeHelperRegistry($this->modelParser, true, $customPath, __DIR__);

    $this->extension = new IdeHelperPropertiesExtension($registry);
    $this->reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
});

it('provides property from IDE helper that is not on real model', function () {
    $classReflection = $this->reflectionProvider->getClass('Fixtures\App\Models\Post');

    // 'display_name' is in IDE helper but not in real model's @property phpdoc
    expect($this->extension->hasProperty($classReflection, 'display_name'))->toBeTrue();

    $property = $this->extension->getProperty($classReflection, 'display_name');
    expect($property->getReadableType())->toBeInstanceOf(StringType::class);
});

it('returns correct type for property', function () {
    $classReflection = $this->reflectionProvider->getClass('Fixtures\App\Models\Post');

    // 'author' is @property-read in IDE helper, not on real model's @property
    expect($this->extension->hasProperty($classReflection, 'author'))->toBeTrue();

    $property = $this->extension->getProperty($classReflection, 'author');
    $type = $property->getReadableType();
    expect($type)->toBeInstanceOf(UnionType::class); // Author|null
});

it('does not provide property already declared on real model', function () {
    $classReflection = $this->reflectionProvider->getClass('Fixtures\App\Models\Post');

    // 'rating' exists on real model's @property — extension should NOT claim it
    expect($this->extension->hasProperty($classReflection, 'rating'))->toBeFalse();
});

it('marks @property-read as not writable', function () {
    $classReflection = $this->reflectionProvider->getClass('Fixtures\App\Models\Post');

    $property = $this->extension->getProperty($classReflection, 'display_name');
    expect($property->isReadable())->toBeTrue()
        ->and($property->isWritable())->toBeFalse();
});

it('marks @property as writable', function () {
    $registry = new IdeHelperRegistry($this->modelParser, true, '', __DIR__);
    $registry->register(new IdeHelperModelData(
        'Fixtures\App\Models\Post',
        ['custom_field' => new IdeHelperPropertyData('custom_field', new StringType, false)],
    ));

    $extension = new IdeHelperPropertiesExtension($registry);
    $classReflection = $this->reflectionProvider->getClass('Fixtures\App\Models\Post');

    $property = $extension->getProperty($classReflection, 'custom_field');
    expect($property->isReadable())->toBeTrue()
        ->and($property->isWritable())->toBeTrue();
});

it('returns false for unknown property', function () {
    $classReflection = $this->reflectionProvider->getClass('Fixtures\App\Models\Post');

    expect($this->extension->hasProperty($classReflection, 'nonexistent_property'))->toBeFalse();
});

it('returns false for class not in IDE helper', function () {
    $classReflection = $this->reflectionProvider->getClass('stdClass');

    expect($this->extension->hasProperty($classReflection, 'anything'))->toBeFalse();
});
