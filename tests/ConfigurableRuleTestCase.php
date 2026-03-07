<?php

namespace ImSuperlative\FilamentPhpstan\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule<\PhpParser\Node>>
 */
class ConfigurableRuleTestCase extends RuleTestCase
{
    /** @var Rule<\PhpParser\Node> */
    private static Rule $rule; // @phpstan-ignore missingType.generics

    public static function useRule(Rule $rule): void // @phpstan-ignore missingType.generics
    {
        self::$rule = $rule;
    }

    protected function getRule(): Rule // @phpstan-ignore missingType.generics
    {
        return self::$rule;
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__).'/extension.neon'];
    }
}
