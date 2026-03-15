<?php

namespace Fixtures\App\Resources\Post\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';
}
