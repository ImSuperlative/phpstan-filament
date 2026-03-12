<?php

namespace Fixtures\App\Resources\PostResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;

class TagsRelationManager extends RelationManager
{
    protected static string $relationship = 'tags';
}
