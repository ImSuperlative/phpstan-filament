<?php

namespace Fixtures\App\RelationshipTests;

use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Fixtures\App\Models\Post;

class RelationshipResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // Valid: 'author' is a BelongsTo on Post
            Select::make('author_id')
                ->relationship('author', 'name'),

            // Valid: 'category' is a BelongsTo on Post
            Select::make('category_id')
                ->relationship('category', 'name'),

            // ERROR: 'writer' is not a relationship on Post
            Select::make('author_id')
                ->relationship('writer', 'name'),

            // ERROR: 'categorie' is not a relationship on Post (typo)
            Select::make('category_id')
                ->relationship('categorie', 'name'),
        ]);
    }
}
