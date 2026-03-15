<?php

use ImSuperlative\PhpstanFilament\Data\ChainWalkResult;
use ImSuperlative\PhpstanFilament\Support\AstHelper;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;

it('walkMethodChain collects method names and visits each call', function () {
    // Build AST: SomeClass::make('test')->foo()->bar()->baz()
    $makeCall = new StaticCall(
        new Name('SomeClass'),
        new Identifier('make'),
        [new Arg(new String_('test'))],
    );

    $fooCall = new MethodCall($makeCall, new Identifier('foo'));
    $barCall = new MethodCall($fooCall, new Identifier('bar'));
    $bazCall = new MethodCall($barCall, new Identifier('baz'));

    $visited = [];

    $result = AstHelper::walkMethodChain($bazCall, function (string $methodName, MethodCall $call) use (&$visited) {
        $visited[] = $methodName;
    });

    expect($result)->toBeInstanceOf(ChainWalkResult::class)
        ->and($result->methodNames)->toBe(['baz', 'bar', 'foo'])
        ->and($result->fieldName)->toBe('test')
        ->and($visited)->toBe(['baz', 'bar', 'foo'])
        ->and($visited)->toHaveCount(3);
});

it('walkMethodChain returns null fieldName when root is not a make call', function () {
    // Build AST: $variable->foo()->bar()
    $variable = new Variable('something');
    $fooCall = new MethodCall($variable, new Identifier('foo'));
    $barCall = new MethodCall($fooCall, new Identifier('bar'));

    $visited = [];

    $result = AstHelper::walkMethodChain($barCall, function (string $methodName, MethodCall $call) use (&$visited) {
        $visited[] = $methodName;
    });

    expect($result->methodNames)->toBe(['bar', 'foo'])
        ->and($result->fieldName)->toBeNull()
        ->and($visited)->toBe(['bar', 'foo']);
});

it('walkMethodChain skips non-Identifier method names', function () {
    // Build AST: SomeClass::make('test')->foo()->{$dynamic}()->bar()
    $makeCall = new StaticCall(
        new Name('SomeClass'),
        new Identifier('make'),
        [new Arg(new String_('test'))],
    );

    $fooCall = new MethodCall($makeCall, new Identifier('foo'));
    // Dynamic method name (Variable instead of Identifier)
    $dynamicCall = new MethodCall($fooCall, new Variable('dynamic'));
    $barCall = new MethodCall($dynamicCall, new Identifier('bar'));

    $visited = [];

    $result = AstHelper::walkMethodChain($barCall, function (string $methodName, MethodCall $call) use (&$visited) {
        $visited[] = $methodName;
    });

    // Dynamic call should be skipped in methodNames but chain still traversed
    expect($result->methodNames)->toBe(['bar', 'foo'])
        ->and($result->fieldName)->toBe('test')
        ->and($visited)->toBe(['bar', 'foo']);
});
