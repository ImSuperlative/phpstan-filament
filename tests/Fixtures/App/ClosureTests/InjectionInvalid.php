<?php

namespace Fixtures\App\ClosureTests;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Feature #9: Invalid closure injection parameters.
 * These SHOULD produce errors.
 */
class InjectionInvalid
{
    public static function formComponents(Schema $schema): Schema
    {
        return $schema->components([
            // ERROR: $old is only valid in afterStateUpdated, not in visible()
            TextInput::make('title')
                ->visible(fn ($old): bool => $old !== null),

            // ERROR: $rowLoop is Column-only, not available on Component
            TextInput::make('slug')
                ->visible(fn ($rowLoop): bool => true),

            // ERROR: $table is not available on Component
            TextInput::make('body')
                ->visible(fn ($table): bool => true),

            // ERROR: $nonexistent is not a valid injection name anywhere
            TextInput::make('status')
                ->visible(fn ($nonexistent): bool => true),

            // ERROR: $data is Action-only, not available on Component
            TextInput::make('meta')
                ->visible(fn ($data): bool => true),

            // ERROR: $records is Action-only, not available on Component
            TextInput::make('other')
                ->disabled(fn ($records): bool => true),
        ]);
    }

    public static function tableColumns(Table $table): Table
    {
        return $table->columns([
            // ERROR: $get is not available on Column
            TextColumn::make('title')
                ->formatStateUsing(fn ($get) => $get('something')),

            // ERROR: $set is not available on Column
            TextColumn::make('slug')
                ->formatStateUsing(fn ($set) => null),

            // ERROR: $operation is not available on Column
            TextColumn::make('status')
                ->formatStateUsing(fn ($operation) => $operation),

            // ERROR: $doesnotexist is not a valid injection on Column (chained call)
            TextColumn::make('event')
                ->badge()
                ->color(fn (string $state, $doesnotexist): string => $state),
        ]);
    }

    public static function actionClosures(): Action
    {
        // ERROR: $err is not a valid injection on Action
        return Action::make('approve')
            ->action(function (array $data, Action $action, $err): void {
                // $err is invalid
            });
    }
}
