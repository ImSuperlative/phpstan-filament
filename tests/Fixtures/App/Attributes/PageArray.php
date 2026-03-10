<?php

namespace Fixtures\App\Attributes;

use ImSuperlative\PhpstanFilament\Attributes\FilamentPage;

#[FilamentPage(['Fixtures\App\Resources\PostResource\Pages\EditPost', 'Fixtures\App\Resources\PostResource\Pages\CreatePost'])]
class PageArray {}
