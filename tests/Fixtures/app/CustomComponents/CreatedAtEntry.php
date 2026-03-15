<?php

namespace Fixtures\App\CustomComponents;

use Filament\Infolists\Components\TextEntry;

class CreatedAtEntry
{
    public static function make(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label('Created Date')
            ->since();
    }
}
