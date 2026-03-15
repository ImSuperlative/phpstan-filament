<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Fixtures\App\Resources\Post\Pages\EditPost;
use ImSuperlative\PhpstanFilament\Attributes\FilamentPage;

use function PHPStan\Testing\assertType;

#[FilamentPage(EditPost::class)]
class AttributeAnnotationAssertions
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->visible(function ($livewire) {
                    assertType('Fixtures\App\Resources\Post\Pages\EditPost', $livewire);

                    return true;
                }),
        ]);
    }
}
