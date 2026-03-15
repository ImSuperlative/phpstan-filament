<?php

namespace ImSuperlative\PhpstanFilament\Tests;

use ImSuperlative\PhpstanFilament\FieldValidationLevel;
use ImSuperlative\PhpstanFilament\Rules\MakeFieldValidation\MakeFieldValidationRule;
use ImSuperlative\PhpstanFilament\Tests\Factories\AggregateFieldValidatorFactory;
use ImSuperlative\PhpstanFilament\Tests\Factories\MakeFieldValidationRuleFactory;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule<\PhpParser\Node>>
 */
class ConfigurableRuleTestCase extends RuleTestCase
{
    /** @var Rule<\PhpParser\Node> */
    private static Rule $rule;

    public static function useRule(Rule $rule): void // @phpstan-ignore missingType.generics
    {
        self::$rule = $rule;
    }

    protected function getRule(): Rule
    {
        return self::$rule;
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            dirname(__DIR__).'/extension.neon',
            __DIR__.'/phpstan-test-services.neon',
        ];
    }

    public static function buildRule(FieldValidationLevel $level): MakeFieldValidationRule
    {
        $container = self::getContainer();

        $validatorFactory = $container
            ->getByType(AggregateFieldValidatorFactory::class)
            ->create($level);

        return $container
            ->getByType(MakeFieldValidationRuleFactory::class)
            ->create($level, $validatorFactory);
    }
}
