<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Resources\PostResource;

class ManagePostComments extends ManageRelatedRecords
{
    protected static string $resource = PostResource::class;

    protected static string $relationship = 'comments';

    public function table(Table $table): Table
    {
        return $table->columns([
            // Valid: 'body' exists on Comment
            TextColumn::make('body'),

            // Valid: 'post.title' — 'post' is a relation on Comment
            TextColumn::make('post.title'),

            // ERROR: 'nonexistent' does not exist on Comment
            TextColumn::make('nonexistent'),
        ]);
    }
}
