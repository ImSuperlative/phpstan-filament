<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

use function PHPStan\Testing\assertType;

class StateNarrowingAssertions
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('price')
                ->numeric()
                ->afterStateUpdated(function ($state) {
                    assertType('float|null', $state);
                }),

            TextInput::make('quantity')
                ->integer()
                ->afterStateUpdated(function ($state) {
                    assertType('float|null', $state);
                }),

            TextInput::make('name')
                ->email()
                ->afterStateUpdated(function ($state) {
                    assertType('string|null', $state);
                }),

            Select::make('tags')
                ->multiple()
                ->afterStateUpdated(function ($state) {
                    assertType('array<int, int|string>', $state);
                }),

            Select::make('category')
                ->afterStateUpdated(function ($state) {
                    assertType('int|string|null', $state);
                }),

            FileUpload::make('attachments')
                ->multiple()
                ->afterStateUpdated(function ($state) {
                    assertType('array<int, string>|null', $state);
                }),

            Radio::make('agree')
                ->boolean()
                ->afterStateUpdated(function ($state) {
                    assertType('int|null', $state);
                }),

            TextInput::make('amount')
                ->numeric()
                ->afterStateHydrated(function ($state) {
                    assertType('float|null', $state);
                }),

            TextInput::make('title_old_test')
                ->afterStateUpdated(function ($state, $old, $oldRaw) {
                    assertType('string|null', $state);
                    assertType('string|null', $old);
                    assertType('string|null', $oldRaw);
                }),

            TextInput::make('price_old_test')
                ->numeric()
                ->afterStateUpdated(function ($state, $old) {
                    assertType('float|null', $state);
                    assertType('float|null', $old);
                }),

            TextInput::make('required_field')
                ->required()
                ->afterStateUpdated(function ($state) {
                    assertType('string|null', $state);
                }),

            TextInput::make('required_numeric')
                ->required()
                ->numeric()
                ->afterStateUpdated(function ($state) {
                    assertType('float|null', $state);
                }),
        ]);
    }
}
