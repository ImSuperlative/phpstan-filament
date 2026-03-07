<?php

namespace Fixtures\App\Resources\PostResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Fixtures\App\Resources\PostResource;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;
}
