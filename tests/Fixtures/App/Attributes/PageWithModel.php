<?php

namespace Fixtures\App\Attributes;

use ImSuperlative\FilamentPhpstan\Attributes\FilamentPage;

#[FilamentPage('Fixtures\App\Resources\PostResource\Pages\EditPost', model: 'Fixtures\App\Models\Post')]
class PageWithModel {}
