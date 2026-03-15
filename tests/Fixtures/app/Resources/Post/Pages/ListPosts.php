<?php

namespace Fixtures\App\Resources\Post\Pages;

use Filament\Resources\Pages\ListRecords;
use Fixtures\App\Resources\Post\PostResource;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;
}
