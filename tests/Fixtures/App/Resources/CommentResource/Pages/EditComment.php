<?php

namespace Fixtures\App\Resources\CommentResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Fixtures\App\Resources\CommentResource;

class EditComment extends EditRecord
{
    protected static string $resource = CommentResource::class;
}
