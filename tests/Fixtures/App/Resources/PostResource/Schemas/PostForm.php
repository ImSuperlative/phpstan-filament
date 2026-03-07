<?php

namespace Fixtures\App\Resources\PostResource\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Fixtures\App\Enums\PostStatus;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(255),

            TextInput::make('slug'),

            MarkdownEditor::make('body'),

            Select::make('status')
                ->options(PostStatus::class)
                ->required(),

            Select::make('category_id')
                ->relationship('category', 'name'),

            Toggle::make('is_featured'),

            TagsInput::make('tags'),

            DateTimePicker::make('published_at'),

            Hidden::make('author_id'),
        ]);
    }
}
