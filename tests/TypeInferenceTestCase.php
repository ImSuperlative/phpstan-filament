<?php

namespace ImSuperlative\FilamentPhpstan\Tests;

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
     * @return iterable<array{0: string, 1: string, 2: mixed}>
     */
    public static function assertTypesForFile(string $file): iterable
    {
        return self::gatherAssertTypes($file);
    }
}
