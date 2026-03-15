<?php

namespace Fixtures\App\Components;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SharedRecordDisplay
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')
                ->description(function ($record) {
                    \PHPStan\Testing\assertType('Fixtures\App\Models\Comment|Fixtures\App\Models\Post', $record);

                    return '';
                }),
        ]);
    }
}
