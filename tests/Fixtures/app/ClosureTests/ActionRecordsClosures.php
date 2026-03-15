<?php

namespace Fixtures\App\ClosureTests;

use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

use function PHPStan\Testing\assertType;

/**
 * Feature #8: $records in bulk action closures should be Collection<int, Post>.
 *
 * @filament-model Fixtures\App\Models\Post
 */
class ActionRecordsClosures
{
    public static function configure(): void {}

    public static function makeBulkAction(): BulkAction
    {
        return BulkAction::make('publishSelected')
            ->action(function (Collection $records) {
                assertType('Illuminate\Database\Eloquent\Collection<int, Fixtures\App\Models\Post>', $records);
            });
    }

    public static function makeSelectedRecordsBulkAction(): BulkAction
    {
        return BulkAction::make('archiveSelected')
            ->action(function (Collection $selectedRecords) {
                assertType('Illuminate\Database\Eloquent\Collection<int, Fixtures\App\Models\Post>', $selectedRecords);
            });
    }

    public static function makeBulkActionWithOtherParams(): BulkAction
    {
        return BulkAction::make('tagSelected')
            ->action(function (Collection $records, array $data) {
                assertType('Illuminate\Database\Eloquent\Collection<int, Fixtures\App\Models\Post>', $records);
                assertType('array', $data);
            });
    }
}
