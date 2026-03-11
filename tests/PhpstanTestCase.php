<?php

namespace ImSuperlative\PhpstanFilament\Tests;

use PHPStan\Testing\PHPStanTestCase as BasePHPStanTestCase;

abstract class PhpstanTestCase extends BasePHPStanTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            ...parent::getAdditionalConfigFiles(),
            dirname(__DIR__).'/extension.neon',
            __DIR__.'/phpstan-test-services.neon',
        ];
    }
}
