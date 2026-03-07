<?php

namespace Fixtures\App\OwnerRecordTests;

use Fixtures\App\MakeFieldTests\ManagePostComments;
use Fixtures\App\Resources\PostResource\RelationManagers\CommentsRelationManager;

use function PHPStan\Testing\assertType;

class OwnerRecordUsage extends CommentsRelationManager
{
    public function testOwnerRecordIsTypedAsPost(): void
    {
        assertType('Fixtures\App\Models\Post|null', $this->getOwnerRecord());
    }

    public function testCallerTypeRelationManager(CommentsRelationManager $rm): void
    {
        assertType('Fixtures\App\Models\Post|null', $rm->getOwnerRecord());
    }

    public function testCallerTypeManageRelatedRecords(ManagePostComments $page): void
    {
        assertType('Fixtures\App\Models\Post|null', $page->getOwnerRecord());
    }
}
