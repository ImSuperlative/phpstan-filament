<?php

// tests/Fixtures/SmokeTest.php

namespace ImSuperlative\FilamentPhpstan\Tests\Fixtures;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

use function PHPStan\Testing\assertType;

class SmokeTestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->afterStateUpdated(function ($state) {
                    assertType('string|null', $state);

                    return $state;
                }),
        ]);
    }
}
