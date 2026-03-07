<?php

// tests/Unit/OptionsEnumNarrowingTypeTest.php

use ImSuperlative\FilamentPhpstan\Tests\TypeInferenceTestCase;

TypeInferenceTestCase::setConfigFiles([
    __DIR__.'/../../extension.neon',
]);

it('narrows $state type from enum class and literal options', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with(
    TypeInferenceTestCase::assertTypesForFile(
        __DIR__.'/../Fixtures/App/ClosureTests/OptionsEnumNarrowingAssertions.php'
    )
);
