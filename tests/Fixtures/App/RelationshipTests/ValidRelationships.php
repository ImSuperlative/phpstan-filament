<?php

namespace Fixtures\App\RelationshipTests;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

/**
 * Feature #6: Valid ->relationship() strings.
 * All relationship names exist on the Post model.
 */
class ValidRelationships
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('author_id')
                ->relationship('author', 'name'),

            Select::make('category_id')
                ->relationship('category', 'name'),

            Select::make('tags')
                ->relationship('tags', 'name')
                ->multiple(),
        ]);
    }
}
