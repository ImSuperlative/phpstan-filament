<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Tests\Rules;

use ImSuperlative\PhpstanFilament\Rules\RedundantEnumRule;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RedundantEnumRule>
 */
class RedundantEnumRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        $reflectionProvider = self::getContainer()->getByType(ReflectionProvider::class);

        return new RedundantEnumRule(
            redundantEnum: true,
            filamentClassHelper: new FilamentClassHelper($reflectionProvider),
        );
    }

    public function test_redundant_enum_calls(): void
    {
        $message = 'Calling ->enum() is unnecessary when ->options() receives an enum class.';
        $tip = 'Remove the ->enum() call. When ->options() receives an enum class, it calls ->enum() automatically.';

        $this->analyse(
            [fixture_path('App/RedundantEnumTests/RedundantEnumFixture.php')],
            [
                [$message, 19, $tip],
                [$message, 24, $tip],
                [$message, 47, $tip],
                [$message, 52, $tip],
                [$message, 57, $tip],
            ],
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
