<?php

namespace ImSuperlative\PhpstanFilament\Tests;

use PHPStan\Testing\TypeInferenceTestCase as BaseTypeInferenceTestCase;

class TypeInferenceTestCase extends BaseTypeInferenceTestCase
{
    /** @var list<string> */
    private static array $configFiles = [];

    /**
     * @param  list<string>  $configFiles
     */
    public static function setConfigFiles(array $configFiles): void
    {
        self::$configFiles = $configFiles;
    }

    public static function getAdditionalConfigFiles(): array
    {
        return self::$configFiles;
    }

    /**
     * Suppress E_DEPRECATED during analysis — mirrors Analyser::collectErrors()
     * which TypeInferenceTestCase bypasses by using NodeScopeResolver directly.
     *
     * @return iterable<array{0: string, 1: string, 2: mixed}>
     */
    public static function assertTypesForFile(string $file): iterable
    {
        set_error_handler(static function (int $errno) {
            return $errno === E_DEPRECATED;
        });

        try {
            return self::gatherAssertTypes($file);
        } finally {
            restore_error_handler();
        }
    }
}
