<?php

namespace Fixtures\App\ClosureTests;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Resources\PostResource\Pages\EditPost;

use function PHPStan\Testing\assertType;

class StateColumnAssertions extends EditPost
{
    public function tableAssertions(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')
                ->formatStateUsing(function ($state) {
                    assertType('string', $state);

                    return strtoupper($state);
                }),

            TextColumn::make('is_featured')
                ->formatStateUsing(function ($state) {
                    assertType('bool', $state);

                    return $state;
                }),

            TextColumn::make('views_count')
                ->formatStateUsing(function ($state) {
                    assertType('int', $state);

                    return number_format($state);
                }),

            // Dot-notation: deferred — $state is mixed at default level 1
            TextColumn::make('author.name')
                ->formatStateUsing(function ($state) {
                    assertType('mixed', $state);

                    return strtoupper($state);
                }),
        ]);
    }
}
