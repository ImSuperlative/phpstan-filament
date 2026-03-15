<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Feature #4: Invalid make() field strings.
 * These SHOULD produce errors — field names don't exist on the Post model.
 */
class InvalidMakeFields
{
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // ERROR: 'tite' is not a Post attribute (typo of 'title')
            TextInput::make('tite'),

            // ERROR: 'description' is not a Post attribute
            TextInput::make('description'),

            // ERROR: 'published' is not a Post attribute (should be 'published_at')
            TextInput::make('published'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            // ERROR: 'name' is not a Post attribute
            TextColumn::make('name'),

            // ERROR: 'writer.name' — 'writer' is not a Post relationship (should be 'author')
            TextColumn::make('writer.name'),
        ]);
    }
}
