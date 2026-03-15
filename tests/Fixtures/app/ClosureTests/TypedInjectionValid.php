<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TypedInjectionValid
{
    public static function formComponents(Schema $schema): Schema
    {
        return $schema->components([
            // Typed Get and Set — valid via ByType
            TextInput::make('title')
                ->visible(fn (Get $get, Set $set): bool => true),

            // Typed Model — valid, Model is subtype of array<string,mixed>|Model|null
            TextInput::make('slug')
                ->visible(fn (Model $record): bool => true),

            // Container-resolvable typed param (unknown name, object type) — valid
            TextInput::make('body')
                ->visible(fn (\Illuminate\Contracts\Auth\Guard $auth): bool => $auth->check()),

            // Self-typed component — valid via instanceof check
            TextInput::make('status')
                ->visible(fn (TextInput $component): bool => true),
        ]);
    }

    public static function tableColumns(Table $table): Table
    {
        return $table->columns([
            // Typed Model on Column — valid
            TextColumn::make('title')
                ->formatStateUsing(fn (Model $record, $state) => $state),
        ]);
    }
}
