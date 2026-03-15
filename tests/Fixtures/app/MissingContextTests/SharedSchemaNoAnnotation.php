<?php

namespace Fixtures\App\MissingContextTests;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SharedSchemaNoAnnotation
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('updated_at')
                ->label('Modified At')
                ->since(),
        ]);
    }
}
