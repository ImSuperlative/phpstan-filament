<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

/**
 * Feature #3: $state typing based on component class.
 *
 * Each closure receives $state typed to the component's state type:
 * - TextInput -> ?string
 * - Toggle -> ?bool
 * - Select (single) -> ?string
 * - CheckboxList -> ?array<string>
 * - DateTimePicker -> ?string
 * - KeyValue -> ?array<string, string>
 * - Repeater -> ?array
 * - TagsInput -> ?array<string>
 * - Hidden -> mixed
 */
class StateClosures
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // $state should be ?string
            TextInput::make('title')
                ->afterStateUpdated(function ($state) {
                    // Extension should type $state as ?string
                    $upper = strtoupper($state ?? '');
                }),

            // $state should be ?bool
            Toggle::make('is_featured')
                ->afterStateUpdated(function ($state) {
                    // Extension should type $state as ?bool
                    $negated = ! $state;
                }),

            // $state should be ?string (single select)
            Select::make('status')
                ->afterStateUpdated(function ($state) {
                    // Extension should type $state as ?string
                    $lower = strtolower($state ?? '');
                }),

            // $state should be ?array<string> (checkbox list)
            CheckboxList::make('permissions')
                ->afterStateUpdated(function ($state) {
                    // Extension should type $state as ?array<string>
                    $count = count($state ?? []);
                }),

            // $state should be ?string
            DateTimePicker::make('published_at')
                ->afterStateUpdated(function ($state) {
                    // Extension should type $state as ?string
                    $parsed = $state !== null ? new \DateTimeImmutable($state) : null;
                }),

            // $state should be ?array<string, string>
            KeyValue::make('metadata')
                ->afterStateUpdated(function ($state) {
                    // Extension should type $state as ?array<string, string>
                    $keys = array_keys($state ?? []);
                }),

            // $state should be ?array
            Repeater::make('items')
                ->afterStateUpdated(function ($state) {
                    // Extension should type $state as ?array
                    $count = count($state ?? []);
                }),

            // $state should be ?array<string>
            TagsInput::make('tags')
                ->afterStateUpdated(function ($state) {
                    // Extension should type $state as ?array<string>
                    $joined = implode(',', $state ?? []);
                }),

            // $state should be mixed
            Hidden::make('secret')
                ->afterStateUpdated(function ($state) {
                    // Extension should type $state as mixed
                    $value = $state;
                }),
        ]);
    }
}
