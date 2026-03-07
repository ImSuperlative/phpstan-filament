<?php

namespace Fixtures;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

use function PHPStan\Testing\assertType;

class OperationLiteralAssertions
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->disabled(function ($operation) {
                    assertType("'create'|'edit'|'view'", $operation);

                    return true;
                })
                ->visible(function ($context) {
                    assertType("'create'|'edit'|'view'", $context);

                    return true;
                })
                ->required(function (string $other) {
                    // Non-operation param — type comes from the hint, not the extension
                    assertType('string', $other);

                    return true;
                }),
        ]);
    }
}
