<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\CustomComponents\AnnotatedHelper;
use Fixtures\App\CustomComponents\CreatedAtEntry;
use Fixtures\App\CustomComponents\EmailDeliveryGroup;
use Fixtures\App\Models\Post;

class CustomComponentResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title'),
            CreatedAtEntry::make(),
            EmailDeliveryGroup::make(),
            AnnotatedHelper::make(),
        ]);
    }
}
