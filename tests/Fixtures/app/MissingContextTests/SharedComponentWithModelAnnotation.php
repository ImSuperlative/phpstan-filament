<?php

namespace Fixtures\App\MissingContextTests;

use Filament\Tables\Columns\TextColumn;

/** @filament-model \Fixtures\App\Models\Post */
class SharedComponentWithModelAnnotation
{
    public static function make(): TextColumn
    {
        return TextColumn::make('title')
            ->label('Title');
    }
}
