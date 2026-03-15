<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Models\Post;

class FullPathValidation extends Resource
{
    protected static ?string $model = Post::class;

    public static function table(Table $table): Table
    {
        return $table->columns([
            // Valid: relationship + property
            TextColumn::make('author.name'),

            // Valid: nested relationships — Post → comments → post → author → name
            TextColumn::make('comments.post.author.name'),

            // Valid: typed property path
            TextColumn::make('options.is_pinned'),

            // Valid: deep typed property path
            TextColumn::make('options.meta.seo_title'),

            // Valid: relationship + typed property — Post → author → name (leaf)
            TextColumn::make('category.name'),

            // Valid: nested relations + nested data — Post → comments → post → options → meta → seo_title
            TextColumn::make('comments.post.options.meta.seo_title'),

            // ERROR: invalid leaf on relationship
            TextColumn::make('author.nonexistent'),

            // ERROR: invalid leaf on nested relationship
            TextColumn::make('comments.post.author.nonexistent'),

            // ERROR: invalid leaf on typed property
            TextColumn::make('options.nonexistent_field'),

            // ERROR: invalid intermediate segment
            TextColumn::make('fakething.name'),

            // ERROR: invalid deep leaf on typed property
            TextColumn::make('options.meta.nonexistent_field'),
        ]);
    }
}
