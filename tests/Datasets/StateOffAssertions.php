<?php

use ImSuperlative\PhpstanFilament\Tests\TypeInferenceTestCase;

dataset('state-toggle-types', function () {
    TypeInferenceTestCase::setConfigFiles([
        __DIR__.'/../../extension.neon',
        __DIR__.'/../Config/state-off.neon',
    ]);

    return TypeInferenceTestCase::assertTypesForFile(
        fixture_path('App/ClosureTests/StateToggleAssertions.php'),
    );
});
