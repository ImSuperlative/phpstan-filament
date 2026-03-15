<?php

namespace Fixtures\App\ClosureTests;

use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Resources\Post\Pages\EditPost;

use function PHPStan\Testing\assertType;

class StateDotNotationAssertions extends EditPost
{
    public function tableAssertions(Table $table): Table
    {
        return $table->columns([
            // Relationship dot-notation: Post::author() → Author::$name
            TextColumn::make('author.name')
                ->formatStateUsing(function ($state) {
                    assertType('string', $state);

                    return strtoupper($state);
                }),

            // Property dot-notation: Post::$options → PostOptions::$meta → PostMeta::$seo_title
            TextEntry::make('options.meta.seo_title')
                ->formatStateUsing(function ($state) {
                    assertType('string', $state);

                    return strtoupper($state);
                }),

            // Collection item dot-notation: Post::$options → PostOptions::$items (Collection<int, PostMeta>) → PostMeta::$seo_title
            TextEntry::make('options.items.seo_title')
                ->formatStateUsing(function ($state) {
                    assertType('string', $state);

                    return strtoupper($state);
                }),

            // Unresolvable intermediate segment — stays mixed
            TextColumn::make('nonexistent.field')
                ->formatStateUsing(function ($state) {
                    assertType('mixed', $state);

                    return $state;
                }),
        ]);
    }
}
