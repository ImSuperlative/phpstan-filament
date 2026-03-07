<?php

namespace Fixtures\App\Schemas;

use Filament\Infolists\Components\TextEntry;

/**
 * @filament-state \Illuminate\Support\Carbon updated_at
 * @filament-state \Illuminate\Support\Carbon created_at
 */
class TimestampEntry
{
    /** @return array<TextEntry> */
    public static function make(): array
    {
        return [
            TextEntry::make('updated_at')
                ->dateTime(),
            TextEntry::make('created_at')
                ->dateTime(),
        ];
    }
}
