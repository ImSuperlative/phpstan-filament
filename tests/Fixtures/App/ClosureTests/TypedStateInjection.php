<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Resources\PostResource\Pages\EditPost;

class TypedStateInjection extends EditPost
{
    public function formComponents(Schema $schema): Schema
    {
        return $schema->components([
            // ERROR: TextInput state is string|null, not array
            TextInput::make('title')
                ->afterStateUpdated(fn (array $state) => $state), // @error-type $state

            // PASS: CheckboxList state IS array
            CheckboxList::make('tags')
                ->afterStateUpdated(fn (array $state) => $state),

            // PASS: string is compatible with string|null
            TextInput::make('slug')
                ->afterStateUpdated(fn (string $state) => $state),
        ]);
    }

    public function tableColumns(Table $table): Table
    {
        return $table->columns([
            // ERROR: Post::$title is string, not array
            TextColumn::make('title')
                ->color(fn (array $state) => 'primary'), // @error-type $state

            // PASS: Post::$views_count is int — int is compatible
            TextColumn::make('views_count')
                ->color(fn (int $state) => 'primary'),

            // PASS: untyped $state — no error from injection rule (type extension handles inference)
            TextColumn::make('title')
                ->color(fn ($state) => 'primary'),

            // PASS: dot-notation — Post::author() → Author::$name is string
            TextColumn::make('author.name')
                ->color(fn (string $state) => 'primary'),

            // ERROR: dot-notation — Post::$options → PostOptions::$meta → PostMeta::$seo_title is string, not array
            TextEntry::make('options.meta.seo_title')
                ->formatStateUsing(fn (array $state) => $state), // @error-type $state
        ]);
    }
}
