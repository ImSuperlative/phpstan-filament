<?php

namespace Fixtures\App\Resources\PostResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Fixtures\App\Resources\PostResource;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
