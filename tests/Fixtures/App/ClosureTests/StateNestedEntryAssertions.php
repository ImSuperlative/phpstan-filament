<?php

namespace Fixtures\App\ClosureTests;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Table;
use Fixtures\App\Resources\PostResource\Pages\EditPost;

use function PHPStan\Testing\assertType;

class StateNestedEntryAssertions extends EditPost
{
    public function tableAssertions(Table $table): Table
    {
        return $table->columns([
            // Single nesting: Post::author() → Author::$name
            RepeatableEntry::make('author')->schema([
                TextEntry::make('name')
                    ->formatStateUsing(function ($state) {
                        assertType('string', $state);

                        return strtoupper($state);
                    }),
            ]),

            // Two-level nesting: Post::$options → PostOptions::$meta → PostMeta::$seo_title
            RepeatableEntry::make('options')->schema([
                RepeatableEntry::make('meta')->schema([
                    TextEntry::make('seo_title')
                        ->formatStateUsing(function ($state) {
                            assertType('string', $state);

                            return strtoupper($state);
                        }),
                ]),
            ]),

            // Unresolvable parent — stays mixed
            RepeatableEntry::make('nonexistent')->schema([
                TextEntry::make('field')
                    ->formatStateUsing(function ($state) {
                        assertType('mixed', $state);

                        return $state;
                    }),
            ]),
        ]);
    }
}
