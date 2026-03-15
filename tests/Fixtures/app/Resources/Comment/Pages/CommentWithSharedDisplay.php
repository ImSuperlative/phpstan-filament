<?php

namespace Fixtures\App\Resources\Comment\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Fixtures\App\Components\SharedRecordDisplay;
use Fixtures\App\Resources\Comment\CommentResource;

class CommentWithSharedDisplay extends ListRecords
{
    protected static string $resource = CommentResource::class;

    public function table(Table $table): Table
    {
        return SharedRecordDisplay::configure($table);
    }
}
