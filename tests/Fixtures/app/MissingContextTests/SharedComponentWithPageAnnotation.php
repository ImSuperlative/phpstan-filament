<?php

namespace Fixtures\App\MissingContextTests;

use Filament\Tables\Columns\TextColumn;

/** @filament-page \Fixtures\App\Resources\PostResource\Pages\EditPost */
class SharedComponentWithPageAnnotation
{
    public static function make(): TextColumn
    {
        return TextColumn::make('title')
            ->label('Title');
    }
}
