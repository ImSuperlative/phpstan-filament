<?php

namespace Fixtures\App\RedundantEnumTests;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;
use Fixtures\App\Enums\PostStatus;

class RedundantEnumFixture
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // ERROR line 20: ->enum() is redundant (options first, enum outermost)
            Select::make('status')
                ->options(PostStatus::class)
                ->enum(PostStatus::class),

            // ERROR line 25: reversed order (enum first, options outermost)
            Select::make('status2')
                ->enum(PostStatus::class)
                ->options(PostStatus::class),

            // OK: ->options() only, no ->enum()
            Select::make('status3')
                ->options(PostStatus::class),

            // OK: ->enum() only, no ->options() with enum class
            Select::make('status4')
                ->enum(PostStatus::class),

            // OK: ->options() with array, not enum — ->enum() is needed
            Select::make('status5')
                ->options(['draft' => 'Draft', 'published' => 'Published'])
                ->enum(PostStatus::class),

            // OK: ->options() with closure — ->enum() is needed
            Select::make('status6')
                ->options(fn (): array => PostStatus::cases())
                ->enum(PostStatus::class),

            // ERROR line 45: Radio with redundant enum
            Radio::make('priority')
                ->options(PostStatus::class)
                ->enum(PostStatus::class),

            // ERROR line 50: CheckboxList with redundant enum
            CheckboxList::make('selected')
                ->options(PostStatus::class)
                ->enum(PostStatus::class),

            // ERROR line 55: ToggleButtons with redundant enum
            ToggleButtons::make('toggle')
                ->options(PostStatus::class)
                ->enum(PostStatus::class),

            // OK: TextInput doesn't have HasOptions trait
            TextInput::make('title'),
        ]);
    }
}
