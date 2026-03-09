<?php

use ImSuperlative\FilamentPhpstan\Tests\ConfigurableRuleTestCase;
use ImSuperlative\FilamentPhpstan\Tests\TypeInferenceTestCase;

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
): \ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureHandlerContext {
    $scopeFactory = \PHPStan\Testing\PHPStanTestCase::getContainer()->getByType(\PHPStan\Analyser\ScopeFactory::class);
    $scope = $scopeFactory->create(\PHPStan\Analyser\ScopeContext::create('test.php'));

    $methodCall = new \PhpParser\Node\Expr\MethodCall(
        new \PhpParser\Node\Expr\Variable('this'),
        new \PhpParser\Node\Identifier('test'),
    );

    return new \ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureHandlerContext(
        scope: $scope,
        methodCall: $methodCall,
        modelClasses: $modelClasses,
        callerClass: $callerClass,
        declaringClass: $declaringClass,
    );
}
