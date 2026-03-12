<?php

use ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\ResourceWithLiteralGetModel;
use ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\ResourceWithLiteralGetModelAndProperty;
use ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\ResourceWithPhpDocGetModel;
use ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs\TestResource;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

beforeEach(function () {
    $this->reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);
});

it('inspects getModel return type for property-based resource', function () {
    $class = $this->reflectionProvider->getClass(TestResource::class);
    $returnType = $class->getMethod('getModel', new OutOfClassScope)
        ->getVariants()[0]
        ->getReturnType();

    dump('Property-based: '.$returnType->describe(\PHPStan\Type\VerbosityLevel::precise()));
});

it('inspects getModel return type for literal return', function () {
    $class = $this->reflectionProvider->getClass(ResourceWithLiteralGetModel::class);
    $returnType = $class->getMethod('getModel', new OutOfClassScope)
        ->getVariants()[0]
        ->getReturnType();

    dump('Literal return: '.$returnType->describe(\PHPStan\Type\VerbosityLevel::precise()));
});

it('inspects getModel return type for phpdoc class-string', function () {
    $class = $this->reflectionProvider->getClass(ResourceWithPhpDocGetModel::class);
    $returnType = $class->getMethod('getModel', new OutOfClassScope)
        ->getVariants()[0]
        ->getReturnType();

    dump('PHPDoc class-string: '.$returnType->describe(\PHPStan\Type\VerbosityLevel::precise()));
});

it('inspects getModel return type for literal return with property', function () {
    $class = $this->reflectionProvider->getClass(ResourceWithLiteralGetModelAndProperty::class);
    $returnType = $class->getMethod('getModel', new OutOfClassScope)
        ->getVariants()[0]
        ->getReturnType();

    dump('Literal + property: '.$returnType->describe(\PHPStan\Type\VerbosityLevel::precise()));
});
