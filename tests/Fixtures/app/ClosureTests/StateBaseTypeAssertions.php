<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

use function PHPStan\Testing\assertType;

class StateBaseTypeAssertions
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->afterStateUpdated(function ($state) {
                    assertType('string|null', $state);
                }),

            Toggle::make('is_featured')
                ->afterStateUpdated(function ($state) {
                    assertType('bool|null', $state);
                }),

            Select::make('status')
                ->afterStateUpdated(function ($state) {
                    assertType('int|string|null', $state);
                }),

            CheckboxList::make('permissions')
                ->afterStateUpdated(function ($state) {
                    assertType('array<int, int|string>', $state);
                }),

            KeyValue::make('metadata')
                ->afterStateUpdated(function ($state) {
                    assertType('array<string, string>|null', $state);
                }),

            Repeater::make('items')
                ->afterStateUpdated(function ($state) {
                    assertType('array|null', $state);
                }),

            TagsInput::make('tags')
                ->afterStateUpdated(function ($state) {
                    assertType('array<int, string>|null', $state);
                }),

            Hidden::make('secret')
                ->afterStateUpdated(function ($state) {
                    assertType('mixed', $state);
                }),
        ]);
    }
}
