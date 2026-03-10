<?php

namespace Fixtures\App\ClosureTests;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Fixtures\App\Enums\PostStatus;

use function PHPStan\Testing\assertType;

class ActionDataClosures
{
    public static function makeAction(): Action
    {
        return Action::make('updateTitle')
            ->schema([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug'),
            ])
            ->action(function (array $data) {
                assertType('array{title: string, slug: string|null}', $data);
            });
    }

    public static function makeActionWithoutForm(): Action
    {
        return Action::make('simple')
            ->action(function (array $data) {
                assertType('array', $data);
            });
    }

    public static function makeActionWithOptions(): Action
    {
        return Action::make('updateTitle')
            ->schema([
                TextInput::make('title')
                    ->required(),
                Select::make('status')
                    ->options([
                        'publish' => 'Publish',
                        'draft' => 'Draft',
                    ]),
            ])
            ->action(function (array $data) {
                assertType("array{title: string, status: 'draft'|'publish'|null}", $data);
            });
    }

    public static function makeActionWithRequiredOptions(): Action
    {
        return Action::make('updateTitle')
            ->schema([
                TextInput::make('title')
                    ->required(),
                Select::make('status')
                    ->required()
                    ->options([
                        'publish' => 'Publish',
                        'draft' => 'Draft',
                    ]),
            ])
            ->action(function (array $data) {
                assertType("array{title: string, status: 'draft'|'publish'}", $data);
            });
    }

    public static function makeActionWithEnum(): Action
    {
        return Action::make('updateTitle')
            ->schema([
                TextInput::make('title')
                    ->required(),
                Select::make('status')
                    ->options(PostStatus::class),
            ])
            ->action(function (array $data) {
                assertType('array{title: string, status: Fixtures\App\Enums\PostStatus|null}', $data);
            });
    }

    public static function makeActionWithRequiredEnum(): Action
    {
        return Action::make('updateTitle')
            ->schema([
                TextInput::make('title')
                    ->required(),
                Select::make('status')
                    ->required()
                    ->options(PostStatus::class),
            ])
            ->action(function (array $data) {
                assertType('array{title: string, status: Fixtures\App\Enums\PostStatus}', $data);
            });
    }

    public static function makeActionWithChainedMethods(): Action
    {
        return Action::make('update')
            ->modalHeading('Update post')
            ->form([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('body')
                    ->required(),
                TextInput::make('slug')
                    ->nullable(),
            ])
            ->requiresConfirmation()
            ->action(function (array $data) {
                assertType('array{title: string, body: string, slug: string|null}', $data);
            });
    }

    public static function makeActionWithMixedComponents(): Action
    {
        return Action::make('settings')
            ->schema([
                TextInput::make('price')
                    ->required()
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
                Select::make('category')
                    ->multiple(),
                Select::make('status'),
            ])
            ->action(function (array $data) {
                assertType('array{price: float, is_active: bool, category: array<int, int|string>, status: int|string|null}', $data);
            });
    }

    public static function makeActionWithNestedSection(): Action
    {
        return Action::make('nested')
            ->schema([
                Section::make('Details')->schema([
                    TextInput::make('title')->required(),
                    TextInput::make('slug'),
                ]),
                Toggle::make('is_active')->required(),
            ])
            ->action(function (array $data) {
                assertType('array{title: string, slug: string|null, is_active: bool}', $data);
            });
    }

    public static function makeActionWithDeeplyNestedSchema(): Action
    {
        return Action::make('complex')
            ->schema([
                Section::make('Post Details')
                    ->description('Enter the post information')
                    ->schema([
                        TextInput::make('title')->required(),
                        Group::make()->schema([
                            TextInput::make('slug'),
                            Select::make('category')
                                ->multiple()
                                ->required(),
                        ]),
                    ]),
                Section::make('Settings')->schema([
                    Toggle::make('is_published')->required(),
                    TextInput::make('meta_description'),
                ]),
            ])
            ->action(function (array $data) {
                assertType('array{title: string, slug: string|null, category: array<int, int|string>, is_published: bool, meta_description: string|null}', $data);
            });
    }

    public static function makeActionWithDeeplyNestedStatePathSchema(): Action
    {
        return Action::make('complex')
            ->schema([
                Section::make('Post Details')
                    ->description('Enter the post information')
                    ->schema([
                        TextInput::make('title')->required(),
                        Fieldset::make('options')
                            ->statePath('options')
                            ->schema([
                                TextInput::make('slug'),
                                Select::make('category')
                                    ->multiple()
                                    ->required(),
                                Fieldset::make('meta')
                                    ->statePath('meta')
                                    ->schema([
                                        TextInput::make('name'),
                                    ]),
                            ]),
                    ]),
                Section::make('Settings')
                    ->statePath('settings')
                    ->schema([
                        Toggle::make('is_published')->required(),
                        TextInput::make('meta_description'),
                    ]),
            ])
            ->action(function (array $data) {
                assertType('array{title: string, options: array{slug: string|null, category: array<int, int|string>, meta: array{name: string|null}}, settings: array{is_published: bool, meta_description: string|null}}', $data);
            });
    }

    public static function makeActionWithRequiredFalse(): Action
    {
        return Action::make('requiredFalse')
            ->schema([
                TextInput::make('title')
                    ->required(false),
                TextInput::make('slug'),
            ])
            ->action(function (array $data) {
                assertType('array{title: string|null, slug: string|null}', $data);
            });
    }

    public static function makeActionWithRequiredClosure(): Action
    {
        return Action::make('requiredClosure')
            ->schema([
                TextInput::make('title')
                    ->required(fn (): bool => true),
                TextInput::make('slug'),
            ])
            ->action(function (array $data) {
                assertType('array{title: string|null, slug: string|null}', $data);
            });
    }
}
