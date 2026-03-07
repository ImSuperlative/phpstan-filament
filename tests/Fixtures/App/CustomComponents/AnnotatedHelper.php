<?php

namespace Fixtures\App\CustomComponents;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Fixtures\App\Models\Email;

/**
 * @filament-field Email latestSubmissionEmail
 */
class AnnotatedHelper
{
    public static function make(): Group
    {
        return Group::make()
            ->schema([
                TextEntry::make('latestSubmissionEmail.sent_at'),
                TextEntry::make('latestSubmissionEmail.nonexistent_field'),
            ]);
    }
}
