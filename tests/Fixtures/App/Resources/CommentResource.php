<?php

namespace Fixtures\App\Resources;

use Filament\Resources\Resource;
use Fixtures\App\Models\Comment;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    /** @return array<string, mixed> */
    public static function getPages(): array
    {
        return [
            'edit' => CommentResource\Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
