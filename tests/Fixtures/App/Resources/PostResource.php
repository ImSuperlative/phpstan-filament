<?php

namespace Fixtures\App\Resources;

use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Fixtures\App\Models\Post;
use Fixtures\App\Resources\PostResource\Pages;
use Fixtures\App\Resources\PostResource\RelationManagers\CommentsRelationManager;
use Fixtures\App\Resources\PostResource\RelationManagers\MediaRelationManager;
use Fixtures\App\Resources\PostResource\RelationManagers\TagsRelationManager;
use Fixtures\App\Resources\PostResource\Schemas\PostForm;
use Fixtures\App\Resources\PostResource\Schemas\PostTable;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostTable::configure($table);
    }

    /** @return array<string, mixed> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
            'view' => Pages\ViewPost::route('/{record}'),
        ];
    }

    /** @return list<mixed> */
    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
            RelationGroup::make('Media', [
                TagsRelationManager::class,
                MediaRelationManager::class,
            ]),
        ];
    }
}
