<?php

use ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\PhpstanFilament\Tests\ConfigurableRuleTestCase;
use ImSuperlative\PhpstanFilament\Tests\TypeInferenceTestCase;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Testing\PHPStanTestCase;

pest()->extend(TypeInferenceTestCase::class)
    ->in('Unit');

pest()->extend(ConfigurableRuleTestCase::class)
    ->in('Rules');

/**
 * Build a ClosureHandlerContext for unit testing handlers.
 *
 * @param  list<string>  $modelClasses
 */
function buildHandlerContext(
    array $modelClasses = [],
    ?string $callerClass = null,
    ?string $declaringClass = null,
): ClosureHandlerContext {
    $scopeFactory = PHPStanTestCase::getContainer()->getByType(ScopeFactory::class);
    $scope = $scopeFactory->create(ScopeContext::create('test.php'));

    $methodCall = new MethodCall(
        new Variable('this'),
        new Identifier('test'),
    );

    return new ClosureHandlerContext(
        scope: $scope,
        methodCall: $methodCall,
        modelClasses: $modelClasses,
        callerClass: $callerClass,
        declaringClass: $declaringClass,
    );
}
