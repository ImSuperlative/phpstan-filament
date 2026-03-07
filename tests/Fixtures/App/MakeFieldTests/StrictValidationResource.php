<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Models\Post;

class StrictValidationResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title'),          // Valid: @property on Post
            TextColumn::make('nonexistent'),    // Invalid at level 2: not on Post
            TextColumn::make('author'),         // Valid: relation method on Post
            TextColumn::make('comments_count'), // Valid: @property-read on Post
            TextColumn::make('summary'),        // Valid: new-style accessor method on Post
        ]);
    }
}
