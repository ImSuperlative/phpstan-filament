<?php

namespace Fixtures\App\Resources\Comment;

use Filament\Resources\Resource;
use Fixtures\App\Models\Comment;
use Fixtures\App\Resources\Comment\Pages\EditComment;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    /** @return array<string, mixed> */
    public static function getPages(): array
    {
        return [
            'edit' => EditComment::route('/{record}/edit'),
        ];
    }
}
