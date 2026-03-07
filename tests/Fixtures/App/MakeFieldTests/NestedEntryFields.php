<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Fixtures\App\Models\Post;

/**
 * Mirrors production pattern: typed property → nested RepeatableEntry
 * e.g. FormVersion has @property /FormSchemaData|null $schema
 *      RepeatableEntry::make('schema.fields') → TextEntry::make('data.type')
 *      Full path: schema.fields.data.type
 *
 * Here: Post has @property /PostOptions|null $options
 *       RepeatableEntry::make('options') → TextEntry::make('meta')
 *       Full path: options.meta (typed property → typed property)
 */
class NestedEntryFields extends Resource
{
    protected static ?string $model = Post::class;

    public static function table(Table $table): Table
    {
        return $table->columns([
            // Direct typed property dot-notation: options is @property PostOptions|null
            // levels 1-2: skip (not a relation), level 3: walk
            RepeatableEntry::make('options')->schema([
                // Becomes options.is_pinned
                TextEntry::make('is_pinned'),

                // Becomes options.meta — typed property on PostOptions
                RepeatableEntry::make('meta')->schema([
                    // Becomes options.meta.seo_title
                    TextEntry::make('seo_title'),
                    // Becomes options.meta.nonexistent_field — invalid at level 3
                    TextEntry::make('nonexistent_field'),
                ]),
            ]),

            // Relation then typed property: author.name
            RepeatableEntry::make('author')->schema([
                TextEntry::make('name'),
                TextEntry::make('nonexistent'),
            ]),

            // MorphTo: commentable on Comment is MorphTo — can't resolve, should not error
            // (tested separately in MorphToFields.php)
        ]);
    }
}
