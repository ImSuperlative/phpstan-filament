<?php

namespace Fixtures\App\OwnerRecordTests;

use Fixtures\App\Resources\Post\RelationManagers\CommentsRelationManager;

use function PHPStan\Testing\assertType;

/**
 * Simulates a shared schema class that is NOT a resource page or relation manager.
 */
class SharedSchemaOwnerRecord
{
    public static function configure(): void {}

    public function testCallerTypeFromSharedClass(CommentsRelationManager $rm): void
    {
        assertType('Fixtures\App\Models\Post|null', $rm->getOwnerRecord());
    }
}
