<?php

namespace Fixtures\App\Resources\Comment\Pages;

use Filament\Resources\Pages\EditRecord;
use Fixtures\App\Resources\Comment\CommentResource;

class EditComment extends EditRecord
{
    protected static string $resource = CommentResource::class;
}
