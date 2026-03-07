<?php

// tests/Unit/OwnerRecordReturnTypeTest.php

use ImSuperlative\FilamentPhpstan\Tests\TypeInferenceTestCase;

TypeInferenceTestCase::setConfigFiles([
    __DIR__.'/../../extension.neon',
]);

it('types getOwnerRecord() as the resource model on a RelationManager subclass', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType);
})->with(
    TypeInferenceTestCase::assertTypesForFile(
        __DIR__.'/../Fixtures/App/OwnerRecordTests/OwnerRecordUsage.php'
    )
);

it('resolves getOwnerRecord() from caller type in a shared class', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType);
})->with(
    TypeInferenceTestCase::assertTypesForFile(
        __DIR__.'/../Fixtures/App/OwnerRecordTests/SharedSchemaOwnerRecord.php'
    )
);
