<?php

use ImSuperlative\PhpstanFilament\Rules\RedundantEnumRule;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use ImSuperlative\PhpstanFilament\Tests\ConfigurableRuleTestCase;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

$message = 'Calling ->enum() is unnecessary when ->options() receives an enum class.';
$tip = 'Remove the ->enum() call. When ->options() receives an enum class, it calls ->enum() automatically.';

function makeRedundantEnumRule(bool $enabled = true): RedundantEnumRule
{
    $reflectionProvider = PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class);

    return new RedundantEnumRule(
        redundantEnum: $enabled,
        filamentClassHelper: new FilamentClassHelper($reflectionProvider),
    );
}

it('reports errors for redundant enum calls', function () use ($message, $tip) {
    ConfigurableRuleTestCase::useRule(makeRedundantEnumRule());
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
    ConfigurableRuleTestCase::useRule(makeRedundantEnumRule(enabled: false));

    $this->analyse(
        [__DIR__.'/../Fixtures/App/RedundantEnumTests/RedundantEnumFixture.php'],
        []
    );
});
