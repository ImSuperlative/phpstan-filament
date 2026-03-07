<?php

namespace Fixtures\App\ClosureTests;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Models\Activity;
use Fixtures\App\Resources\PostResource\Pages\EditPost;

use function PHPStan\Testing\assertType;

class TableQueryOverrideAssertions extends EditPost
{
    public function tableAssertions(Table $table): Table
    {
        return $table
            ->query(Activity::query())
            ->columns([
                TextColumn::make('event_type')
                    ->formatStateUsing(function ($state) {
                        assertType('string', $state);

                        return $state;
                    })
                    ->description(function ($record) {
                        assertType('Fixtures\App\Models\Activity', $record);

                        return $record->description;
                    }),
            ]);
    }
}
