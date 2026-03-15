<?php

namespace Fixtures\App\Resources\Post\Pages;

use Filament\Resources\Pages\EditRecord;
use Fixtures\App\Resources\Post\PostResource;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;
}
