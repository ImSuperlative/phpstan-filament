<?php

namespace Fixtures\App\ClosureTests;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Feature #9: Valid closure injection parameters.
 * None of these should produce errors.
 */
class InjectionValid
{
    public static function formComponents(Schema $schema): Schema
    {
        return $schema->components([
            // All standard Component injections — valid
            TextInput::make('title')
                ->afterStateUpdated(function ($state, $old, $oldRaw, Get $get, Set $set, $record, $livewire, $model, $operation, $component) {
                    // All valid for afterStateUpdated on Component
                }),

            // $context is alias for $operation — valid
            TextInput::make('slug')
                ->visible(fn ($context): bool => $context === 'create'),

            // Typed parameter (DI from container) — always valid
            TextInput::make('body')
                ->visible(fn (\Illuminate\Contracts\Auth\Guard $auth): bool => $auth->check()),

            // $rawState — valid for Component
            TextInput::make('status')
                ->visible(fn ($rawState): bool => $rawState !== null),

            // $parentRepeaterItemIndex — valid for Component
            TextInput::make('nested')
                ->visible(fn ($parentRepeaterItemIndex): bool => $parentRepeaterItemIndex > 0),
        ]);
    }

    public static function tableColumns(Table $table): Table
    {
        return $table->columns([
            // All standard Column injections — valid
            TextColumn::make('title')
                ->formatStateUsing(fn ($state, $record, $livewire, $rowLoop, $table, $column) => $state),

            // $value on formatStateUsing — valid method-specific injection
            TextColumn::make('subtitle')
                ->formatStateUsing(fn ($value) => strtoupper($value)),

            // $query and $direction on sortable — valid method-specific injection
            TextColumn::make('duration')
                ->sortable(query: fn ($query, $direction) => $query->orderByRaw('duration '.$direction)),
        ]);
    }

    public static function formComponentsWithMethodSpecific(Schema $schema): Schema
    {
        return $schema->components([
            // $value on formatStateUsing — valid for Component
            TextInput::make('title')
                ->formatStateUsing(fn ($value) => strtoupper($value)),

            // $query on modifyQueryUsing — valid for Component
            TextInput::make('category')
                ->relationship('category', modifyQueryUsing: fn ($query) => $query->active()),
        ]);
    }

    public static function actionClosures(): Action
    {
        // Valid Action injections — $data, $record, $action are all valid
        return Action::make('approve')
            ->action(function (array $data, Action $action, $record): void {
                // All valid for Action
            });
    }

    public static function actionWithContainerDiReservedName(): Action
    {
        // Container DI with a reserved name ($settings) — valid because it's object-typed
        return Action::make('deploy')
            ->action(function (\Illuminate\Contracts\Auth\Guard $settings): void {
                // $settings is a reserved name but typed as an object — container DI
            })
            ->visible(fn (\Illuminate\Contracts\Auth\Guard $settings): bool => $settings->check());
    }
}
