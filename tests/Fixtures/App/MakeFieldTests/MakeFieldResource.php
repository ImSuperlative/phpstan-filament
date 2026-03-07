<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Models\Post;

class MakeFieldResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function table(Table $table): Table
    {
        return $table->columns([
            // Valid: 'author' IS a BelongsTo on Post
            TextColumn::make('author.name'),

            // ERROR: 'writer' is NOT a relationship on Post (unknown method)
            TextColumn::make('writer.name'),

            // ERROR: 'getFullTitle' exists on Post but is NOT a relation
            TextColumn::make('getFullTitle.something'),
        ]);
    }
}
