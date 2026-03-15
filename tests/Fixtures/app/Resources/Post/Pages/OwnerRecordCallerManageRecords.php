<?php

namespace Fixtures\App\Resources\Post\Pages;

use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Fixtures\App\Resources\Post\PostResource;

use function PHPStan\Testing\assertType;

class OwnerRecordCallerManageRecords extends ManageRelatedRecords
{
    protected static string $resource = PostResource::class;

    protected static string $relationship = 'comments';

    public function form(Schema $schema): Schema
    {
        assertType('Fixtures\App\Models\Post|null', $this->getOwnerRecord());

        return $schema;
    }
}
