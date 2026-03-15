<?php

namespace Fixtures\App\Resources\Post\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Fixtures\App\Components\SharedRecordDisplay;
use Fixtures\App\Resources\Post\PostResource;

class PostWithSharedDisplay extends ListRecords
{
    protected static string $resource = PostResource::class;

    public function table(Table $table): Table
    {
        return SharedRecordDisplay::configure($table);
    }
}
