<?php

namespace Fixtures\App\Resources\CommentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Fixtures\App\Components\SharedRecordDisplay;
use Fixtures\App\Resources\CommentResource;

class CommentWithSharedDisplay extends ListRecords
{
    protected static string $resource = CommentResource::class;

    public function table(Table $table): Table
    {
        return SharedRecordDisplay::configure($table);
    }
}
