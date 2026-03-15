<?php

namespace Fixtures\App\Resources\Post\Pages;

use Filament\Resources\Pages\CreateRecord;
use Fixtures\App\Resources\Post\PostResource;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
