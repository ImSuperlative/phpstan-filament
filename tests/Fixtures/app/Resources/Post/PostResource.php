<?php

namespace Fixtures\App\Resources\Post;

use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Fixtures\App\ClosureTests\MapTypeAssertions;
use Fixtures\App\ClosureTests\RecordClosureAssertions;
use Fixtures\App\ClosureTests\StateColumnAssertions;
use Fixtures\App\ClosureTests\StateDotNotationAssertions;
use Fixtures\App\ClosureTests\StateNestedEntryAssertions;
use Fixtures\App\ClosureTests\TableQueryOverrideAssertions;
use Fixtures\App\ClosureTests\TypedStateInjection;
use Fixtures\App\MakeFieldTests\ManagePostComments;
use Fixtures\App\Models\Post;
use Fixtures\App\Resources\Post\Pages\ClosureTestPage;
use Fixtures\App\Resources\Post\Pages\CreatePost;
use Fixtures\App\Resources\Post\Pages\EditPost;
use Fixtures\App\Resources\Post\Pages\ListPosts;
use Fixtures\App\Resources\Post\Pages\ViewPost;
use Fixtures\App\Resources\Post\RelationManagers\CommentsRelationManager;
use Fixtures\App\Resources\Post\RelationManagers\MediaRelationManager;
use Fixtures\App\Resources\Post\RelationManagers\TagsRelationManager;
use Fixtures\App\Resources\Post\Schemas\PostForm;
use Fixtures\App\Resources\Post\Schemas\PostTable;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostTable::configure($table);
    }

    /** @return array<string, mixed> */
    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
            'view' => ViewPost::route('/{record}'),
            'closure-tests' => ClosureTestPage::route('/closure-tests'),
            'record-assertions' => RecordClosureAssertions::route('/record-assertions'),
            'state-column' => StateColumnAssertions::route('/state-column'),
            'state-dot' => StateDotNotationAssertions::route('/state-dot'),
            'state-nested' => StateNestedEntryAssertions::route('/state-nested'),
            'map-type' => MapTypeAssertions::route('/map-type'),
            'table-query' => TableQueryOverrideAssertions::route('/table-query'),
            'typed-state' => TypedStateInjection::route('/typed-state'),
            'manage-comments' => ManagePostComments::route('/{record}/comments'),
        ];
    }

    /** @return list<mixed> */
    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
            RelationGroup::make('Media', [
                TagsRelationManager::class,
                MediaRelationManager::class,
            ]),
        ];
    }
}
