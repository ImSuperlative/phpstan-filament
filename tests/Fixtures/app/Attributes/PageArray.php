<?php

namespace Fixtures\App\Attributes;

use ImSuperlative\PhpstanFilament\Attributes\FilamentPage;

#[FilamentPage(['Fixtures\App\Resources\Post\Pages\EditPost', 'Fixtures\App\Resources\Post\Pages\CreatePost'])]
class PageArray {}
