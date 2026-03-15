<?php

namespace Fixtures\App\Resources\Post\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;

class TagsRelationManager extends RelationManager
{
    protected static string $relationship = 'tags';
}
