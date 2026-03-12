<?php

namespace Fixtures\App\Resources\PostResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';
}
