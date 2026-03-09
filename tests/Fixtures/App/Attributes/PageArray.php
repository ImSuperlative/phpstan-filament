<?php

namespace Fixtures\App\Attributes;

use ImSuperlative\FilamentPhpstan\Attributes\FilamentPage;

#[FilamentPage(['Fixtures\App\Resources\PostResource\Pages\EditPost', 'Fixtures\App\Resources\PostResource\Pages\CreatePost'])]
class PageArray {}
