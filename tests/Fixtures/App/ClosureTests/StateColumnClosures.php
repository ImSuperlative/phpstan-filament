<?php

namespace Fixtures\App\ClosureTests;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Feature #3: $state in table columns typed from model attribute.
 *
 * Requires model context (Post) to resolve attribute types.
 * - TextColumn::make('title') -> $state is string (from Post::$title)
 * - IconColumn::make('is_featured') -> $state is bool (from Post::$is_featured)
 * - TextColumn::make('views_count') -> $state is int (from Post::$views_count)
 * - TextColumn::make('author.name') -> $state is string (resolved through relationship)
 *
 * When model context is unavailable, $state falls back to mixed.
 */
class StateColumnClosures
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')
                ->formatStateUsing(fn ($state): string => strtoupper($state)),

            IconColumn::make('is_featured')
                ->boolean(),

            TextColumn::make('views_count')
                ->formatStateUsing(fn ($state): string => number_format($state)),

            // Dot-notation through relationship
            TextColumn::make('author.name')
                ->formatStateUsing(fn ($state): string => strtoupper($state)),

            // Custom computed state — $state type comes from closure return type, not model
            TextColumn::make('display_name')
                ->state(fn ($record): string => $record->title.' by '.$record->author->name),
        ]);
    }
}
