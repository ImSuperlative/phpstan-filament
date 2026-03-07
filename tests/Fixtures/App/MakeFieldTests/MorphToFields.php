<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Models\Comment;

class MorphToFields extends Resource
{
    protected static ?string $model = Comment::class;

    public static function table(Table $table): Table
    {
        return $table->columns([
            // Valid: 'commentable' IS a MorphTo on Comment — can't resolve model, but should not error
            TextColumn::make('commentable.title'),

            // Valid: 'author' IS a BelongsTo on Comment — resolvable
            TextColumn::make('author.name'),
        ]);
    }
}
