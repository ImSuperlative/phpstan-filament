<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Fixtures\App\Resources\Post\Pages\EditPost;

use function PHPStan\Testing\assertType;

class RecordClosureAssertions extends EditPost
{
    public function formAssertions(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->default(function ($record) {
                    assertType('Fixtures\App\Models\Post|null', $record);

                    return $record !== null ? $record->title : 'New Post';
                })
                ->visible(function ($record) {
                    assertType('Fixtures\App\Models\Post|null', $record);

                    return $record !== null;
                }),
        ]);
    }

    public function tableAssertions(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')
                ->description(function ($record) {
                    assertType('Fixtures\App\Models\Post', $record);

                    return $record->slug ?? '';
                }),
        ]);
    }

    public function replicaAssertions(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->default(function ($replica) {
                    assertType('Fixtures\App\Models\Post|null', $replica);

                    return $replica?->title ?? 'Copy';
                }),
        ]);
    }

    public function typedParamAssertions(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->default(function (\Fixtures\App\Models\Post $record) {
                    assertType('Fixtures\App\Models\Post', $record);

                    return $record->title;
                }),
        ]);
    }
}
