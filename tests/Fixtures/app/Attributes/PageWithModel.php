<?php

namespace Fixtures\App\Attributes;

use ImSuperlative\PhpstanFilament\Attributes\FilamentPage;

#[FilamentPage('Fixtures\App\Resources\Post\Pages\EditPost', model: 'Fixtures\App\Models\Post')]
class PageWithModel {}
