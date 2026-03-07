<?php

namespace Fixtures\App\Resources\PostResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Fixtures\App\Resources\PostResource;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;
}
