<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

use function PHPStan\Testing\assertType;

/**
 * When stateClosure is disabled, $state should be mixed.
 * This fixture is used with a phpstan config that sets stateClosure: false.
 */
class StateToggleAssertions
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->afterStateUpdated(function ($state) {
                    assertType('mixed', $state);
                }),
        ]);
    }
}
