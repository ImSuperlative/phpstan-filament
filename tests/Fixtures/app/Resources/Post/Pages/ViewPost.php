<?php

namespace Fixtures\App\Resources\Post\Pages;

use Filament\Resources\Pages\ViewRecord;
use Fixtures\App\Resources\Post\PostResource;

class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;
}
