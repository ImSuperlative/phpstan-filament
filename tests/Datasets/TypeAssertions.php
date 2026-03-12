<?php

use ImSuperlative\PhpstanFilament\Tests\BatchTypeInferenceTestCase;

$results = BatchTypeInferenceTestCase::batchGatherAssertTypes([
    fixture_path('SmokeTest.php'),
    fixture_path('OperationLiteralAssertions.php'),
    fixture_path('App/ClosureTests/RecordClosureAssertions.php'),
    fixture_path('App/ClosureTests/StateBaseTypeAssertions.php'),
    fixture_path('App/ClosureTests/StateColumnAssertions.php'),
    fixture_path('App/ClosureTests/StateNarrowingAssertions.php'),
    fixture_path('App/ClosureTests/MapTypeAssertions.php'),
    fixture_path('App/ClosureTests/OptionsEnumNarrowingAssertions.php'),
    fixture_path('App/ClosureTests/FilamentPageAnnotationAssertions.php'),
    fixture_path('App/ClosureTests/AttributeAnnotationAssertions.php'),
    fixture_path('App/ClosureTests/ActionRecordsClosures.php'),
    fixture_path('App/ClosureTests/ActionDataClosures.php'),
    fixture_path('App/ClosureTests/TableQueryOverrideAssertions.php'),
    fixture_path('App/OwnerRecordTests/OwnerRecordUsage.php'),
    fixture_path('App/OwnerRecordTests/SharedSchemaOwnerRecord.php'),
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
