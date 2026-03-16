<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Tests\Rules\MakeFieldValidation;

use ImSuperlative\PhpstanFilament\FieldValidationLevel;
use ImSuperlative\PhpstanFilament\Rules\MakeFieldValidation\MakeFieldValidationRule;
use ImSuperlative\PhpstanFilament\Tests\Factories\AggregateFieldValidatorFactory;
use ImSuperlative\PhpstanFilament\Tests\Factories\MakeFieldValidationRuleFactory;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MakeFieldValidationRule>
 */
class MakeFieldLevel0Test extends RuleTestCase
{

    protected function getRule(): Rule
    {
        $container = self::getContainer();
        $level = FieldValidationLevel::Level_0;

        $validatorFactory = $container
            ->getByType(AggregateFieldValidatorFactory::class)
            ->create($level);

        return $container
            ->getByType(MakeFieldValidationRuleFactory::class)
            ->create($level, $validatorFactory);
    }

    public function test_skips_all_validation_at_level0(): void
    {
        $this->analyse(
            [fixture_path('App/MakeFieldTests/MakeFieldResource.php')],
            [],
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            project_root('extension.neon'),
            tests_path('phpstan-test-services.neon'),
        ];
    }
}
