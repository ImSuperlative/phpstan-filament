<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Tests\Integration;

use ImSuperlative\PhpstanFilament\Tests\Traits\TracksMemory;
use ImSuperlative\PhpstanFilament\Tests\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AllTypeInferenceTest extends TypeInferenceTestCase
{
    use TracksMemory;

    public static function getAdditionalConfigFiles(): array
    {
        return [
            project_root('extension.neon'),
            tests_path('phpstan-test-services.neon'),
            tests_path('config/types.neon'),
        ];
    }

    /** @return iterable<mixed> */
    public static function dataFileAsserts(): iterable
    {
        yield from self::assertTypesForFile(fixture_path('SmokeTest.php'));
        yield from self::assertTypesForFile(fixture_path('OperationLiteralAssertions.php'));
        yield from self::assertTypesForFile(fixture_path('App/ClosureTests/RecordClosureAssertions.php'));
        yield from self::assertTypesForFile(fixture_path('App/ClosureTests/StateBaseTypeAssertions.php'));
        yield from self::assertTypesForFile(fixture_path('App/ClosureTests/StateColumnAssertions.php'));
        yield from self::assertTypesForFile(fixture_path('App/ClosureTests/StateNarrowingAssertions.php'));
        yield from self::assertTypesForFile(fixture_path('App/ClosureTests/MapTypeAssertions.php'));
        yield from self::assertTypesForFile(fixture_path('App/ClosureTests/OptionsEnumNarrowingAssertions.php'));
        yield from self::assertTypesForFile(fixture_path('App/ClosureTests/FilamentPageAnnotationAssertions.php'));
        yield from self::assertTypesForFile(fixture_path('App/ClosureTests/FilamentPageAnnotationUnionAssertions.php'));
        yield from self::assertTypesForFile(fixture_path('App/ClosureTests/FilamentPageAnnotationMultiTagAssertions.php'));
        yield from self::assertTypesForFile(fixture_path('App/ClosureTests/ActionRecordsClosures.php'));
        yield from self::assertTypesForFile(fixture_path('App/ClosureTests/ActionDataClosures.php'));
        yield from self::assertTypesForFile(fixture_path('App/ClosureTests/TableQueryOverrideAssertions.php'));
        yield from self::assertTypesForFile(fixture_path('App/Resources/Post/RelationManagers/OwnerRecordCallerRelationManager.php'));
        yield from self::assertTypesForFile(fixture_path('App/Resources/Post/Pages/OwnerRecordCallerManageRecords.php'));
        // yield from self::assertTypesForFile(fixture_path('App/OwnerRecordTests/SharedSchemaOwnerRecord.php')); // need proper test fixture
        yield from self::assertTypesForFile(fixture_path('App/OwnerRecordTests/SharedSchemaOwnerRecordManage.php'));
    }

    #[DataProvider('dataFileAsserts')]
    public function test_file_asserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
