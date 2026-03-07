<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Models\Post;

class AggregateFields extends Resource
{
    protected static ?string $model = Post::class;

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('comments_count'),           // Valid: comments is a relation, count needs no column
            TextColumn::make('fakething_count'),           // Invalid: fakething is not a relation
            TextColumn::make('fakething_avg_score'),       // Invalid: fakething is not a relation
            TextColumn::make('comments_avg_rating'),       // Invalid at level 3: comments is valid, but rating not on Comment
            TextColumn::make('comments_max_created_at'),   // Valid: comments is valid, created_at exists on Comment
        ]);
    }
}
