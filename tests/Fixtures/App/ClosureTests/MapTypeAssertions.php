<?php

namespace Fixtures\App\ClosureTests;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

use function PHPStan\Testing\assertType;

class MapTypeAssertions extends \Fixtures\App\Filament\Resources\PostResource\Pages\EditPost
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // $get and $set should get their map types
            TextInput::make('title')
                ->visible(function ($get, $set) {
                    assertType('Filament\Schemas\Components\Utilities\Get', $get);
                    assertType('Filament\Schemas\Components\Utilities\Set', $set);

                    return true;
                }),

            // $livewire should get HasSchemas & Component intersection
            TextInput::make('slug')
                ->visible(function ($livewire) {
                    assertType('Filament\Schemas\Contracts\HasSchemas&Livewire\Component', $livewire);

                    return true;
                }),

            // $component should get static type
            TextInput::make('body')
                ->visible(function ($component) {
                    assertType('static(Filament\Schemas\Components\Component)', $component);

                    return true;
                }),

            // $model should get class-string type
            TextInput::make('excerpt')
                ->visible(function ($model) {
                    assertType('class-string<Illuminate\Database\Eloquent\Model>|null', $model);

                    return true;
                }),
        ]);
    }
}
