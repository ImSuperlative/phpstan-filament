<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;
use Fixtures\App\Enums\PostStatus;

use function PHPStan\Testing\assertType;

class OptionsEnumNarrowingAssertions
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // === ENUM NARROWING ===

            Select::make('status')
                ->enum(PostStatus::class)
                ->afterStateUpdated(function ($state) {
                    assertType('Fixtures\App\Enums\PostStatus|null', $state);
                }),

            Select::make('status_via_options')
                ->options(PostStatus::class)
                ->afterStateUpdated(function ($state) {
                    assertType('Fixtures\App\Enums\PostStatus|null', $state);
                }),

            Select::make('statuses')
                ->enum(PostStatus::class)
                ->multiple()
                ->afterStateUpdated(function ($state) {
                    assertType('array<int, Fixtures\App\Enums\PostStatus>', $state);
                }),

            Radio::make('priority')
                ->enum(PostStatus::class)
                ->afterStateUpdated(function ($state) {
                    assertType('Fixtures\App\Enums\PostStatus|null', $state);
                }),

            CheckboxList::make('selected_statuses')
                ->enum(PostStatus::class)
                ->afterStateUpdated(function ($state) {
                    assertType('array<int, Fixtures\App\Enums\PostStatus>', $state);
                }),

            ToggleButtons::make('toggle_status')
                ->enum(PostStatus::class)
                ->afterStateUpdated(function ($state) {
                    assertType('Fixtures\App\Enums\PostStatus|null', $state);
                }),

            ToggleButtons::make('toggle_statuses')
                ->enum(PostStatus::class)
                ->multiple()
                ->afterStateUpdated(function ($state) {
                    assertType('array<int, Fixtures\App\Enums\PostStatus>', $state);
                }),

            // === LITERAL OPTIONS NARROWING ===

            Select::make('size')
                ->options([
                    'small' => 'Small',
                    'medium' => 'Medium',
                    'large' => 'Large',
                ])
                ->afterStateUpdated(function ($state) {
                    assertType("'large'|'medium'|'small'|null", $state);
                }),

            Select::make('sizes')
                ->options([
                    'small' => 'Small',
                    'medium' => 'Medium',
                    'large' => 'Large',
                ])
                ->multiple()
                ->afterStateUpdated(function ($state) {
                    assertType("array<int, 'large'|'medium'|'small'>", $state);
                }),

            Radio::make('confirm')
                ->options([
                    'yes' => 'Yes',
                    'no' => 'No',
                ])
                ->afterStateUpdated(function ($state) {
                    assertType("'no'|'yes'|null", $state);
                }),

            Select::make('rating')
                ->options([
                    1 => 'Poor',
                    2 => 'Average',
                    3 => 'Good',
                ])
                ->afterStateUpdated(function ($state) {
                    assertType('1|2|3|null', $state);
                }),

            // === DYNAMIC / NON-RESOLVABLE OPTIONS ===

            Select::make('dynamic')
                ->options(fn (): array => ['a' => 'A', 'b' => 'B'])
                ->afterStateUpdated(function ($state) {
                    assertType('int|string|null', $state);
                }),
        ]);
    }
}
