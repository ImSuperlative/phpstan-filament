<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Models\Post;

class PropertyReadRelation extends Resource
{
    protected static ?string $model = Post::class;

    public static function table(Table $table): Table
    {
        return $table->columns([
            // Valid: 'reviewer' is @property-read Author|null — detected as relation via fallback
            TextColumn::make('reviewer.name'),

            // Valid: 'author' IS a BelongsTo on Post — detected via method
            TextColumn::make('author.name'),
        ]);
    }
}
