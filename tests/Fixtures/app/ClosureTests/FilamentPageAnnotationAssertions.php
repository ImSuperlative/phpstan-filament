<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

use function PHPStan\Testing\assertType;

/**
 * @filament-page EditPost
 */
class FilamentPageAnnotationAssertions
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->visible(function ($livewire) {
                    assertType('Fixtures\App\ClosureTests\EditPost', $livewire);

                    return true;
                }),
        ]);
    }
}
