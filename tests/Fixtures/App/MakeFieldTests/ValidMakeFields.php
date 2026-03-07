<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Feature #4: Valid make() field strings.
 * All of these correspond to real Post model attributes or relationships.
 */
class ValidMakeFields
{
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title'),           // Post::$title exists
            TextInput::make('slug'),            // Post::$slug exists
            TextInput::make('body'),            // Post::$body exists
            Select::make('status'),             // Post::$status exists
            Toggle::make('is_featured'),        // Post::$is_featured exists
            Select::make('category_id'),        // Post::$category_id exists
            Select::make('author_id'),          // Post::$author_id exists
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title'),          // Direct attribute
            TextColumn::make('author.name'),    // Dot notation through relationship
            TextColumn::make('status'),         // Enum attribute
            IconColumn::make('is_featured'),    // Boolean attribute
            TextColumn::make('comments_count'), // Aggregate count
        ]);
    }
}
