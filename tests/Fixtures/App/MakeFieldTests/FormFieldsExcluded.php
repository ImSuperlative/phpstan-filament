<?php

namespace Fixtures\App\MakeFieldTests;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Fixtures\App\Models\Post;

class FormFieldsExcluded extends Resource
{
    protected static ?string $model = Post::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nonexistent_field'),
            TextInput::make('confirmation'),
        ]);
    }
}
