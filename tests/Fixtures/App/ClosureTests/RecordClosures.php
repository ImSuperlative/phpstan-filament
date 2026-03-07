<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Models\Post;

/**
 * Feature #2: $record in closures should be typed to the resource's model.
 *
 * Context: These methods are called from PostResource (via PostForm/PostTable),
 * so $record should be typed as Post.
 */
class RecordClosures
{
    /**
     * $record in form field closures.
     * Inside PostResource context, $record should be ?Post (nullable during create).
     */
    public static function formWithRecord(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->default(fn ($record) => $record !== null ? $record->title : 'New Post')
                ->visible(fn ($record): bool => $record !== null)
                ->disabled(fn ($record): bool => $record !== null && $record->is_featured),
        ]);
    }

    /**
     * $record in table column closures.
     * Inside PostResource context, $record should be Post (non-null in table).
     */
    public static function tableWithRecord(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')
                ->description(fn ($record): string => $record->slug ?? ''),

            TextColumn::make('display_status')
                ->state(fn (Post $record): string => $record->status->value),
        ]);
    }
}
