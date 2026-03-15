<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;
use Fixtures\App\Enums\PostStatus;

/**
 * Options and enum narrowing for Select, Radio, CheckboxList, ToggleButtons.
 */
class OptionsEnumClosures
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')
                ->enum(PostStatus::class)
                ->afterStateUpdated(function ($state) {
                    $value = $state?->value;
                }),

            Select::make('statuses')
                ->enum(PostStatus::class)
                ->multiple()
                ->afterStateUpdated(function ($state) {
                    foreach ($state as $status) {
                        $value = $status->value;
                    }
                }),

            Radio::make('priority')
                ->enum(PostStatus::class)
                ->afterStateUpdated(function ($state) {
                    $value = $state?->value;
                }),

            CheckboxList::make('selected_statuses')
                ->enum(PostStatus::class)
                ->afterStateUpdated(function ($state) {
                    $count = count($state);
                }),

            ToggleButtons::make('toggle_status')
                ->enum(PostStatus::class)
                ->afterStateUpdated(function ($state) {
                    $value = $state?->value;
                }),

            Select::make('size')
                ->options([
                    'small' => 'Small',
                    'medium' => 'Medium',
                    'large' => 'Large',
                ])
                ->afterStateUpdated(function ($state) {
                    $isSmall = $state === 'small';
                }),

            Radio::make('confirm')
                ->options([
                    'yes' => 'Yes',
                    'no' => 'No',
                ])
                ->afterStateUpdated(function ($state) {
                    $isYes = $state === 'yes';
                }),

            Select::make('dynamic')
                ->options(fn (): array => ['a' => 'A', 'b' => 'B'])
                ->afterStateUpdated(function ($state) {
                    $value = $state;
                }),
        ]);
    }
}
