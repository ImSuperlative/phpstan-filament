<?php

namespace Fixtures\App\RelationshipTests;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

/**
 * Feature #6: Invalid ->relationship() strings.
 * These SHOULD produce errors.
 */
class InvalidRelationships
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // ERROR: 'writer' is not a relationship on Post (should be 'author')
            Select::make('author_id')
                ->relationship('writer', 'name'),

            // ERROR: 'categorie' is not a relationship on Post (typo)
            Select::make('category_id')
                ->relationship('categorie', 'name'),
        ]);
    }
}
