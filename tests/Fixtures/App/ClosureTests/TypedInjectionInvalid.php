<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TypedInjectionInvalid
{
    public static function formComponents(Schema $schema): Schema
    {
        return $schema->components([
            // ERROR: $record expects array<string,mixed>|Model|null, not string
            TextInput::make('title')
                ->visible(fn (string $record): bool => true), // @error-type $record
        ]);
    }

    public static function tableColumns(Table $table): Table
    {
        return $table->columns([
            // ERROR: $record expects array<string,mixed>|Model|null, not string
            TextColumn::make('title')
                ->formatStateUsing(fn (string $record) => $record), // @error-type $record
        ]);
    }
}
