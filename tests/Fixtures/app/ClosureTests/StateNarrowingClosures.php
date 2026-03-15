<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * State narrowing via method calls on components.
 * State casts narrow in ALL contexts (they transform the actual value).
 */
class StateNarrowingClosures
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('price')
                ->numeric()
                ->afterStateUpdated(function ($state) {
                    $doubled = $state * 2;
                }),

            TextInput::make('quantity')
                ->integer()
                ->afterStateUpdated(function ($state) {
                    $incremented = $state + 1;
                }),

            TextInput::make('name')
                ->afterStateUpdated(function ($state) {
                    $upper = strtoupper($state ?? '');
                }),

            TextInput::make('email')
                ->email()
                ->afterStateUpdated(function ($state) {
                    $lower = strtolower($state ?? '');
                }),

            Select::make('tags')
                ->multiple()
                ->afterStateUpdated(function ($state) {
                    $count = count($state);
                }),

            Select::make('category')
                ->afterStateUpdated(function ($state) {
                    $isNull = $state === null;
                }),

            FileUpload::make('attachments')
                ->multiple()
                ->afterStateUpdated(function ($state) {
                    $count = count($state ?? []);
                }),

            FileUpload::make('avatar')
                ->afterStateUpdated(function ($state) {
                    $hasFile = $state !== null;
                }),

            Radio::make('agree')
                ->boolean()
                ->afterStateUpdated(function ($state) {
                    $isTrue = $state === 1;
                }),

            Radio::make('preference')
                ->afterStateUpdated(function ($state) {
                    $isNull = $state === null;
                }),
        ]);
    }
}
