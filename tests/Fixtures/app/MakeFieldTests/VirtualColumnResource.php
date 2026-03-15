<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Models\Post;

class VirtualColumnResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function table(Table $table): Table
    {
        return $table->columns([
            // Virtual: has ->state(), should not be validated
            TextColumn::make('custom_display')
                ->state(fn ($record) => $record->title.' - '.$record->status),

            // Virtual: has ->getStateUsing(), should not be validated
            TextColumn::make('computed_value')
                ->getStateUsing(fn () => 'static value'),

            // NOT virtual: ->formatStateUsing() does NOT make it virtual
            TextColumn::make('title')
                ->formatStateUsing(fn ($state) => strtoupper($state)),
        ]);
    }
}
