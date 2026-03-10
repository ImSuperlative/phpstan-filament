<?php

use ImSuperlative\PhpstanFilament\Tests\BatchTypeInferenceTestCase;

$results = BatchTypeInferenceTestCase::batchGatherAssertTypes([
    __DIR__.'/../Fixtures/SmokeTest.php',
    __DIR__.'/../Fixtures/OperationLiteralAssertions.php',
    __DIR__.'/../Fixtures/App/ClosureTests/RecordClosureAssertions.php',
    __DIR__.'/../Fixtures/App/ClosureTests/StateBaseTypeAssertions.php',
    __DIR__.'/../Fixtures/App/ClosureTests/StateColumnAssertions.php',
    __DIR__.'/../Fixtures/App/ClosureTests/StateNarrowingAssertions.php',
    __DIR__.'/../Fixtures/App/ClosureTests/MapTypeAssertions.php',
    __DIR__.'/../Fixtures/App/ClosureTests/OptionsEnumNarrowingAssertions.php',
    __DIR__.'/../Fixtures/App/ClosureTests/FilamentPageAnnotationAssertions.php',
    __DIR__.'/../Fixtures/App/ClosureTests/AttributeAnnotationAssertions.php',
    __DIR__.'/../Fixtures/App/ClosureTests/ActionRecordsClosures.php',
    __DIR__.'/../Fixtures/App/ClosureTests/ActionDataClosures.php',
    __DIR__.'/../Fixtures/App/ClosureTests/TableQueryOverrideAssertions.php',
    __DIR__.'/../Fixtures/App/OwnerRecordTests/OwnerRecordUsage.php',
    __DIR__.'/../Fixtures/App/OwnerRecordTests/SharedSchemaOwnerRecord.php',
]);

dataset('smoke-test', $results['SmokeTest.php']);
dataset('operation-literal-types', $results['OperationLiteralAssertions.php']);
dataset('record-closure-types', $results['RecordClosureAssertions.php']);
dataset('state-base-types', $results['StateBaseTypeAssertions.php']);
dataset('state-column-types', $results['StateColumnAssertions.php']);
dataset('state-narrowing-types', $results['StateNarrowingAssertions.php']);
dataset('map-closure-types', $results['MapTypeAssertions.php']);
dataset('options-enum-narrowing-types', $results['OptionsEnumNarrowingAssertions.php']);
dataset('filament-page-annotation-types', $results['FilamentPageAnnotationAssertions.php']);
dataset('attribute-annotation-types', $results['AttributeAnnotationAssertions.php']);
dataset('action-records-types', $results['ActionRecordsClosures.php']);
dataset('action-data-types', $results['ActionDataClosures.php']);
dataset('table-query-override-types', $results['TableQueryOverrideAssertions.php']);
dataset('owner-record-types', $results['OwnerRecordUsage.php']);
dataset('shared-schema-owner-record-types', $results['SharedSchemaOwnerRecord.php']);
