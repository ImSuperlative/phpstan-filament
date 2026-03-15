<?php

namespace Fixtures\App\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * Used by multiple resources — call-site tracing should detect ambiguity.
 * If needed, add @filament-model annotation as fallback.
 */
class SharedForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required(),
        ]);
    }
}
