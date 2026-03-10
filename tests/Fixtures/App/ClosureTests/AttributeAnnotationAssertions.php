<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Fixtures\App\Resources\PostResource\Pages\CreatePost;
use Fixtures\App\Resources\PostResource\Pages\EditPost;
use ImSuperlative\PhpstanFilament\Attributes\FilamentPage;

use function PHPStan\Testing\assertType;

#[FilamentPage(EditPost::class)]
class AttributePageAssertions
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->visible(function ($livewire) {
                    assertType('Fixtures\App\Resources\PostResource\Pages\EditPost', $livewire);

                    return true;
                }),
        ]);
    }
}

#[FilamentPage([EditPost::class, CreatePost::class])]
class AttributePageUnionAssertions
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->visible(function ($livewire) {
                    assertType('Fixtures\App\Resources\PostResource\Pages\CreatePost|Fixtures\App\Resources\PostResource\Pages\EditPost', $livewire);

                    return true;
                }),
        ]);
    }
}

#[FilamentPage(EditPost::class)]
#[FilamentPage(CreatePost::class)]
class AttributePageMultiAssertions
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->visible(function ($livewire) {
                    assertType('Fixtures\App\Resources\PostResource\Pages\CreatePost|Fixtures\App\Resources\PostResource\Pages\EditPost', $livewire);

                    return true;
                }),
        ]);
    }
}
