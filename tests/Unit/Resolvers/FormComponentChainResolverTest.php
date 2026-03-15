<?php

use Fixtures\App\Enums\PostStatus;
use ImSuperlative\PhpstanFilament\Data\ChainAnalysis;
use ImSuperlative\PhpstanFilament\Resolvers\FormComponentChainResolver;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantStringType;

function makeStaticMake(string $name): StaticCall
{
    return new StaticCall(
        new Name('Filament\Forms\Components\Select'),
        new Identifier('make'),
        [new Arg(new String_($name))],
    );
}

function makeChainedCall(string $methodName, array $args, StaticCall|MethodCall $var): MethodCall
{
    return new MethodCall($var, new Identifier($methodName), $args);
}

function makeMockScope(?string $componentClass = null, ?ConstantStringType $constStringType = null): Scope
{
    $scope = Mockery::mock(Scope::class);

    $exprType = Mockery::mock(\PHPStan\Type\Type::class);
    $exprType->shouldReceive('getObjectClassNames')
        ->andReturn($componentClass !== null ? [$componentClass] : []);

    $scope->shouldReceive('getType')
        ->andReturn($exprType)
        ->byDefault();

    if ($constStringType !== null) {
        $argType = Mockery::mock(\PHPStan\Type\Type::class);
        $argType->shouldReceive('getConstantStrings')
            ->andReturn([$constStringType]);

        $scope->shouldReceive('getType')
            ->with(Mockery::type(ClassConstFetch::class))
            ->andReturn($argType);
    }

    return $scope;
}

it('resolves a simple chain with correct fieldName, methodCalls, and defaults', function () {
    $make = makeStaticMake('status');
    $chain = makeChainedCall('required', [], $make);

    $scope = makeMockScope('Filament\Forms\Components\Select');
    $resolver = new FormComponentChainResolver;

    $result = $resolver->resolve($chain, $scope);

    expect($result)->toBeInstanceOf(ChainAnalysis::class)
        ->and($result->fieldName)->toBe('status')
        ->and($result->methodCalls)->toBe(['required'])
        ->and($result->componentClass)->toBe('Filament\Forms\Components\Select')
        ->and($result->enumClass)->toBeNull()
        ->and($result->literalOptionKeys)->toBeNull()
        ->and($result->isMultiple)->toBeFalse();
});

it('detects multiple() in the chain', function () {
    $make = makeStaticMake('tags');
    $chain = makeChainedCall('multiple', [], $make);
    $chain = makeChainedCall('searchable', [], $chain);

    $scope = makeMockScope('Filament\Forms\Components\Select');
    $resolver = new FormComponentChainResolver;

    $result = $resolver->resolve($chain, $scope);

    expect($result->isMultiple)->toBeTrue()
        ->and($result->methodCalls)->toBe(['searchable', 'multiple']);
});

it('respects isInherentlyMultiple flag', function () {
    $make = makeStaticMake('tags');
    $chain = makeChainedCall('searchable', [], $make);

    $scope = makeMockScope('Filament\Forms\Components\CheckboxList');
    $resolver = new FormComponentChainResolver;

    $result = $resolver->resolve($chain, $scope, isInherentlyMultiple: true);

    expect($result->isMultiple)->toBeTrue();
});

it('extracts literal option keys from options() call', function () {
    $make = makeStaticMake('status');
    $optionsArray = new Array_([
        new ArrayItem(new String_('Draft'), new String_('draft')),
        new ArrayItem(new String_('Published'), new String_('published')),
        new ArrayItem(new String_('Archived'), new Int_(3)),
    ]);
    $chain = makeChainedCall('options', [new Arg($optionsArray)], $make);

    $scope = makeMockScope('Filament\Forms\Components\Select');
    $resolver = new FormComponentChainResolver;

    $result = $resolver->resolve($chain, $scope);

    expect($result->literalOptionKeys)->toBe(['draft', 'published', 3]);
});

it('extracts enum class from enum() call', function () {
    $make = makeStaticMake('status');
    $classConst = new ClassConstFetch(
        new Name(PostStatus::class),
        new Identifier('class'),
    );
    $chain = makeChainedCall('enum', [new Arg($classConst)], $make);

    $constStringType = new ConstantStringType(PostStatus::class);
    $scope = makeMockScope('Filament\Forms\Components\Select', $constStringType);
    $resolver = new FormComponentChainResolver;

    $result = $resolver->resolve($chain, $scope);

    expect($result->enumClass)->toBe(PostStatus::class);
});
