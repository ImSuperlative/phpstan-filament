<?php

// tests/Unit/StateDotNotationTypeTest.php

use ImSuperlative\FilamentPhpstan\Tests\TypeInferenceTestCase;

TypeInferenceTestCase::setConfigFiles([
    __DIR__.'/../Fixtures/Config/dot-notation-level3.neon',
]);

it('resolves dot-notation state types at makeFieldValidation level 3', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with(
    TypeInferenceTestCase::assertTypesForFile(
        __DIR__.'/../Fixtures/App/ClosureTests/StateDotNotationAssertions.php'
    )
);
