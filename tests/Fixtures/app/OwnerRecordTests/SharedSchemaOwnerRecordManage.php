<?php

namespace Fixtures\App\OwnerRecordTests;

use Fixtures\App\MakeFieldTests\ManagePostComments;

use function PHPStan\Testing\assertType;

/**
 * Simulates a shared schema class that is NOT a resource page or relation manager.
 */
class SharedSchemaOwnerRecordManage
{
    public static function configure(): void {}

    public function testCallerTypeManageRelatedFromSharedClass(ManagePostComments $page): void
    {
        assertType('Fixtures\App\Models\Post|null', $page->getOwnerRecord());
    }
}
