<?php

namespace Fixtures\App\MissingContextTests;

use Filament\Tables\Columns\TextColumn;

class SharedComponentNoAnnotation
{
    public static function make(): TextColumn
    {
        return TextColumn::make('name')
            ->label('Name')
            ->sortable();
    }
}
