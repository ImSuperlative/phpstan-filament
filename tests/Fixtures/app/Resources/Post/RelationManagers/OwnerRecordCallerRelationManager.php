<?php

namespace Fixtures\App\Resources\Post\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Fixtures\App\Resources\Post\PostResource;

use function PHPStan\Testing\assertType;

class OwnerRecordCallerRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $relatedResource = PostResource::class;

    public function form(Schema $schema): Schema
    {
        assertType('Fixtures\App\Models\Post|null', $this->getOwnerRecord());

        return $schema;
    }
}
