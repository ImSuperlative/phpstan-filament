<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Fixtures\App\Resources\PostResource\Pages\CreatePost;
use Fixtures\App\Resources\PostResource\Pages\EditPost;

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
                    assertType('Fixtures\App\Resources\PostResource\Pages\EditPost', $livewire);

                    return true;
                }),
        ]);
    }
}

/**
 * @filament-page EditPost|CreatePost
 */
class FilamentPageAnnotationUnionAssertions
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

/**
 * @filament-page EditPost
 * @filament-page CreatePost
 */
class FilamentPageAnnotationMultiTagAssertions
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
