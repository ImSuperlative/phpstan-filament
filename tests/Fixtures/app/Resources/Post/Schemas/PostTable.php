<?php

namespace Fixtures\App\Resources\Post\Schemas;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\ClosureTests\StateColumnClosures;

class PostTable
{
    public static function configure(Table $table): Table
    {
        StateColumnClosures::configure($table);

        return $table->columns([
            TextColumn::make('title')
                ->searchable()
                ->sortable(),

            TextColumn::make('author.name')
                ->label('Author'),

            TextColumn::make('status'),

            IconColumn::make('is_featured')
                ->boolean(),

            TextColumn::make('comments_count')
                ->counts('comments'),

            TextColumn::make('published_at')
                ->dateTime(),

            TextColumn::make('created_at')
                ->dateTime(),
        ]);
    }
}
