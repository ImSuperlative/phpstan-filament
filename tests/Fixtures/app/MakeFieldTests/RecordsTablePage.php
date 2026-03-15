<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class RecordsTablePage
{
    public function table(Table $table): Table
    {
        return $table
            ->records(Collection::make([
                ['name' => 'Alice', 'score' => 100],
            ]))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('score'),
            ]);
    }
}
