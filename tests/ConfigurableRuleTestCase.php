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

    /** @var list<\PHPStan\Collectors\Collector<\PhpParser\Node, mixed>> */
    private static array $collectors = [];

    public static function useRule(Rule $rule): void // @phpstan-ignore missingType.generics
    {
        self::$rule = $rule;
    }

    /** @param list<\PHPStan\Collectors\Collector<\PhpParser\Node, mixed>> $collectors */
    public static function useCollectors(array $collectors): void
    {
        self::$collectors = $collectors;
    }

    protected function getRule(): Rule // @phpstan-ignore missingType.generics
    {
        return self::$rule;
    }

    /** @return list<\PHPStan\Collectors\Collector<\PhpParser\Node, mixed>> */
    protected function getCollectors(): array
    {
        return self::$collectors;
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__).'/extension.neon'];
    }
}
