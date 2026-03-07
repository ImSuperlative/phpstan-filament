<?php

namespace Fixtures\App\CustomComponents;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;

class EmailDeliveryGroup
{
    public static function make(): Group
    {
        return Group::make()
            ->schema([
                self::submissionSection(),
            ]);
    }

    private static function submissionSection(): Section
    {
        return Section::make('Submission Email')
            ->schema([
                TextEntry::make('latestSubmissionEmail.sent_at'),
                TextEntry::make('latestSubmissionEmail.delivered_at'),
                TextEntry::make('latestSubmissionEmail.nonexistent_field'),
            ]);
    }
}
