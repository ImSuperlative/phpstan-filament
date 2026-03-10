<?php

use ImSuperlative\PhpstanFilament\Data\IdeHelperMethodData;
use ImSuperlative\PhpstanFilament\Data\IdeHelperModelData;
use ImSuperlative\PhpstanFilament\Extensions\IdeHelper\IdeHelperMethodsExtension;
use ImSuperlative\PhpstanFilament\Parser\TypeStringParser;
use ImSuperlative\PhpstanFilament\Support\IdeHelperModelParser;
use ImSuperlative\PhpstanFilament\Support\IdeHelperRegistry;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;

beforeEach(function () {
    /** @var \PHPStan\Parser\Parser $parser */
    $parser = PHPStanTestCase::getContainer()->getService('defaultAnalysisParser');

    $typeStringParser = TypeStringParser::make();

    $modelParser = new IdeHelperModelParser($parser, $typeStringParser->getLexer(), $typeStringParser->getPhpDocParser());

    // Use manual registration — barryvdh only has native Model methods
    $this->registry = new IdeHelperRegistry($modelParser, true, '', __DIR__);
    $this->registry->register(new IdeHelperModelData(
        'Fixtures\App\Models\Post',
        [],
        [
            'customQuery' => new IdeHelperMethodData('customQuery', new ObjectType('Illuminate\Database\Eloquent\Builder'), true),
            'instanceHelper' => new IdeHelperMethodData('instanceHelper', new StringType, false),
        ],
    ));

    $this->extension = new IdeHelperMethodsExtension($this->registry);
    $this->reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
});

it('provides method from IDE helper', function () {
    $classReflection = $this->reflectionProvider->getClass('Fixtures\App\Models\Post');

    expect($this->extension->hasMethod($classReflection, 'customQuery'))->toBeTrue();

    $method = $this->extension->getMethod($classReflection, 'customQuery');
    expect($method->getName())->toBe('customQuery')
        ->and($method->isStatic())->toBeTrue();
});

it('provides non-static method', function () {
    $classReflection = $this->reflectionProvider->getClass('Fixtures\App\Models\Post');

    $method = $this->extension->getMethod($classReflection, 'instanceHelper');
    expect($method->isStatic())->toBeFalse()
        ->and($method->isPublic())->toBeTrue()
        ->and($method->getVariants())->toHaveCount(1);
});

it('returns false for unknown method', function () {
    $classReflection = $this->reflectionProvider->getClass('Fixtures\App\Models\Post');

    expect($this->extension->hasMethod($classReflection, 'nonexistent_method'))->toBeFalse();
});

it('returns false for class not in IDE helper', function () {
    $classReflection = $this->reflectionProvider->getClass('stdClass');

    expect($this->extension->hasMethod($classReflection, 'anything'))->toBeFalse();
});

it('does not provide method already on real model', function () {
    $classReflection = $this->reflectionProvider->getClass('Fixtures\App\Models\Post');

    $this->registry->register(new IdeHelperModelData(
        'Fixtures\App\Models\Post',
        [],
        ['author' => new IdeHelperMethodData('author', new ObjectType('Illuminate\Database\Eloquent\Relations\BelongsTo'))],
    ));

    expect($this->extension->hasMethod($classReflection, 'author'))->toBeFalse();
});
