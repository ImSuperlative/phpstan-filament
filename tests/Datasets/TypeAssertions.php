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
    fixture_path('App/ClosureTests/FilamentPageAnnotationUnionAssertions.php'),
    fixture_path('App/ClosureTests/FilamentPageAnnotationMultiTagAssertions.php'),
    fixture_path('App/ClosureTests/AttributeAnnotationAssertions.php'),
    fixture_path('App/ClosureTests/AttributePageUnionAssertions.php'),
    fixture_path('App/ClosureTests/AttributePageMultiAssertions.php'),
    fixture_path('App/ClosureTests/ActionRecordsClosures.php'),
    fixture_path('App/ClosureTests/ActionDataClosures.php'),
    fixture_path('App/ClosureTests/TableQueryOverrideAssertions.php'),
    // fixture_path('App/OwnerRecordTests/OwnerRecordUsage.php'),
    fixture_path('App/Resources/Post/RelationManagers/OwnerRecordCallerRelationManager.php'),
    fixture_path('App/Resources/Post/Pages/OwnerRecordCallerManageRecords.php'),
    fixture_path('App/OwnerRecordTests/SharedSchemaOwnerRecord.php'),
    fixture_path('App/OwnerRecordTests/SharedSchemaOwnerRecordManage.php'),
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
dataset('filament-page-annotation-union-types', $results['FilamentPageAnnotationUnionAssertions.php']);
dataset('filament-page-annotation-multi-tag-types', $results['FilamentPageAnnotationMultiTagAssertions.php']);
dataset('attribute-annotation-types', $results['AttributeAnnotationAssertions.php']);
dataset('attribute-annotation-union-types', $results['AttributePageUnionAssertions.php']);
dataset('attribute-annotation-multi-types', $results['AttributePageMultiAssertions.php']);
dataset('action-records-types', $results['ActionRecordsClosures.php']);
dataset('action-data-types', $results['ActionDataClosures.php']);
dataset('table-query-override-types', $results['TableQueryOverrideAssertions.php']);
// dataset('owner-record-self-types', $results['OwnerRecordUsage.php']);
dataset('owner-record-caller-rm-types', $results['OwnerRecordCallerRelationManager.php']);
dataset('owner-record-caller-manage-types', $results['OwnerRecordCallerManageRecords.php']);
dataset('shared-schema-owner-record-types', $results['SharedSchemaOwnerRecord.php']);
dataset('shared-schema-owner-record-manage-types', $results['SharedSchemaOwnerRecordManage.php']);
