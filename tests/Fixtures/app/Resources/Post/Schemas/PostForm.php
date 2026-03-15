<?php

namespace Fixtures\App\Resources\Post\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Fixtures\App\ClosureTests\ActionDataClosures;
use Fixtures\App\ClosureTests\ActionRecordsClosures;
use Fixtures\App\ClosureTests\AttributeAnnotationAssertions;
use Fixtures\App\ClosureTests\AttributePageMultiAssertions;
use Fixtures\App\ClosureTests\AttributePageUnionAssertions;
use Fixtures\App\ClosureTests\FilamentPageAnnotationAssertions;
use Fixtures\App\ClosureTests\FilamentPageAnnotationMultiTagAssertions;
use Fixtures\App\ClosureTests\FilamentPageAnnotationUnionAssertions;
use Fixtures\App\ClosureTests\OperationClosures;
use Fixtures\App\ClosureTests\OptionsEnumClosures;
use Fixtures\App\ClosureTests\OptionsEnumNarrowingAssertions;
use Fixtures\App\ClosureTests\StateBaseTypeAssertions;
use Fixtures\App\ClosureTests\StateClosures;
use Fixtures\App\ClosureTests\StateNarrowingAssertions;
use Fixtures\App\ClosureTests\StateNarrowingClosures;
use Fixtures\App\ClosureTests\StateToggleAssertions;
use Fixtures\App\Enums\PostStatus;
use Fixtures\App\OwnerRecordTests\SharedSchemaOwnerRecord;
use Fixtures\App\OwnerRecordTests\SharedSchemaOwnerRecordManage;
use Fixtures\App\Resources\Post\Pages\OwnerRecordCallerManageRecords;
use Fixtures\App\Resources\Post\RelationManagers\OwnerRecordCallerRelationManager;
use Fixtures\OperationLiteralAssertions;
use ImSuperlative\PhpstanFilament\Tests\Fixtures\SmokeTestForm;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        StateClosures::configure($schema);
        StateBaseTypeAssertions::configure($schema);
        StateNarrowingClosures::configure($schema);
        StateNarrowingAssertions::configure($schema);
        StateToggleAssertions::configure($schema);
        OptionsEnumClosures::configure($schema);
        OptionsEnumNarrowingAssertions::configure($schema);
        OperationClosures::configure($schema);
        FilamentPageAnnotationAssertions::configure($schema);
        FilamentPageAnnotationUnionAssertions::configure($schema);
        FilamentPageAnnotationMultiTagAssertions::configure($schema);
        AttributeAnnotationAssertions::configure($schema);
        AttributePageUnionAssertions::configure($schema);
        AttributePageMultiAssertions::configure($schema);
        OperationLiteralAssertions::configure($schema);
        SmokeTestForm::configure($schema);
        ActionDataClosures::configure();
        ActionRecordsClosures::configure();
        OwnerRecordCallerRelationManager::configure();
        OwnerRecordCallerManageRecords::configure();
        SharedSchemaOwnerRecord::configure();
        SharedSchemaOwnerRecordManage::configure();

        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(255),

            TextInput::make('slug'),

            MarkdownEditor::make('body'),

            Select::make('status')
                ->options(PostStatus::class)
                ->required(),

            Select::make('category_id')
                ->relationship('category', 'name'),

            Toggle::make('is_featured'),

            TagsInput::make('tags'),

            DateTimePicker::make('published_at'),

            Hidden::make('author_id'),
        ]);
    }
}
