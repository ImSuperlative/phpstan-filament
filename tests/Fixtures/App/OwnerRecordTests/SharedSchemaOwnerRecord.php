<?php

namespace Fixtures\App\OwnerRecordTests;

use Fixtures\App\MakeFieldTests\ManagePostComments;
use Fixtures\App\Resources\PostResource\RelationManagers\CommentsRelationManager;

use function PHPStan\Testing\assertType;

/**
 * Simulates a shared schema class that is NOT a resource page or relation manager.
 */
class SharedSchemaOwnerRecord
{
    public function testCallerTypeFromSharedClass(CommentsRelationManager $rm): void
    {
        assertType('Fixtures\App\Models\Post|null', $rm->getOwnerRecord());
    }

    public function testCallerTypeManageRelatedFromSharedClass(ManagePostComments $page): void
    {
        assertType('Fixtures\App\Models\Post|null', $page->getOwnerRecord());
    }
}
