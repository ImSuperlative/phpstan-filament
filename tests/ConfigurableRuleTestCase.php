<?php

namespace ImSuperlative\PhpstanFilament\Tests;

use ImSuperlative\PhpstanFilament\FieldValidationLevel;
use ImSuperlative\PhpstanFilament\Resolvers\ComponentContextResolver;
use ImSuperlative\PhpstanFilament\Resolvers\FieldPathResolver;
use ImSuperlative\PhpstanFilament\Resolvers\PhpDocAnnotationParser;
use ImSuperlative\PhpstanFilament\Rules\MakeFieldValidation\AggregateFieldValidator;
use ImSuperlative\PhpstanFilament\Rules\MakeFieldValidation\MakeFieldValidationRule;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use ImSuperlative\PhpstanFilament\Support\ModelReflectionHelper;
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
        return [dirname(__DIR__).'/extension.neon'];
    }

    public static function buildRule(FieldValidationLevel $level): MakeFieldValidationRule
    {
        $container = self::getContainer();

        return new MakeFieldValidationRule(
            level: $level,
            modelReflectionHelper: $container->getByType(ModelReflectionHelper::class),
            filamentClassHelper: $container->getByType(FilamentClassHelper::class),
            componentContextResolver: $container->getByType(ComponentContextResolver::class),
            phpDocParser: $container->getByType(PhpDocAnnotationParser::class),
            fieldPathResolver: $container->getByType(FieldPathResolver::class),
            aggregateFieldValidator: new AggregateFieldValidator(
                $level,
                $container->getByType(ModelReflectionHelper::class),
            ),
        );
    }
}
