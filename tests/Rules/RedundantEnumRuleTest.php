<?php

use ImSuperlative\FilamentPhpstan\Rules\RedundantEnumRule;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use ImSuperlative\FilamentPhpstan\Tests\ConfigurableRuleTestCase;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

$message = 'Calling ->enum() is unnecessary when ->options() receives an enum class.';
$tip = 'Remove the ->enum() call. When ->options() receives an enum class, it calls ->enum() automatically.';

beforeAll(function () {
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);

    ConfigurableRuleTestCase::useRule(new RedundantEnumRule(
        redundantEnum: true,
        filamentClassHelper: new FilamentClassHelper($reflectionProvider),
    ));
});

it('reports errors for redundant enum calls', function () use ($message, $tip) {
    $this->analyse(
        [__DIR__.'/../Fixtures/App/RedundantEnumTests/RedundantEnumFixture.php'],
        [
            [$message, 19, $tip],
            [$message, 24, $tip],
            [$message, 47, $tip],
            [$message, 52, $tip],
            [$message, 57, $tip],
        ]
    );
});

it('skips validation when the rule is disabled', function () {
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);

    ConfigurableRuleTestCase::useRule(new RedundantEnumRule(
        redundantEnum: false,
        filamentClassHelper: new FilamentClassHelper($reflectionProvider),
    ));

    $this->analyse(
        [__DIR__.'/../Fixtures/App/RedundantEnumTests/RedundantEnumFixture.php'],
        []
    );
});
