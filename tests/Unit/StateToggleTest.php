<?php

// tests/Unit/StateToggleTest.php

use ImSuperlative\FilamentPhpstan\Tests\TypeInferenceTestCase;

// This test uses a config with stateClosure: false
TypeInferenceTestCase::setConfigFiles([
    __DIR__.'/../../extension.neon',
    __DIR__.'/../phpstan-state-off.neon',
]);

it('leaves $state as mixed when stateClosure is disabled', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with(
    TypeInferenceTestCase::assertTypesForFile(
        __DIR__.'/../Fixtures/App/ClosureTests/StateToggleAssertions.php'
    )
);
