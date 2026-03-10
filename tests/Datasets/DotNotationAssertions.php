<?php

use ImSuperlative\PhpstanFilament\Tests\TypeInferenceTestCase;

dataset('dot-notation-types', function () {
    TypeInferenceTestCase::setConfigFiles([
        __DIR__.'/../Config/dot-notation-level3.neon',
    ]);

    return TypeInferenceTestCase::assertTypesForFile(
        __DIR__.'/../Fixtures/App/ClosureTests/StateDotNotationAssertions.php',
    );
});

dataset('nested-entry-types', function () {
    TypeInferenceTestCase::setConfigFiles([
        __DIR__.'/../Config/dot-notation-level3.neon',
    ]);

    return TypeInferenceTestCase::assertTypesForFile(
        __DIR__.'/../Fixtures/App/ClosureTests/StateNestedEntryAssertions.php',
    );
});
