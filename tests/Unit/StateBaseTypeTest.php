<?php

// tests/Unit/StateBaseTypeTest.php

use ImSuperlative\PhpstanFilament\Tests\TypeInferenceTestCase;

TypeInferenceTestCase::setConfigFiles([
    __DIR__.'/../../extension.neon',
]);

it('types $state based on component class from ComponentStateMap', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with(
    TypeInferenceTestCase::assertTypesForFile(
        __DIR__.'/../Fixtures/App/ClosureTests/StateBaseTypeAssertions.php'
    )
);
