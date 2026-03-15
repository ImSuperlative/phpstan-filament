<?php

namespace Fixtures\App\Attributes;

use ImSuperlative\PhpstanFilament\Attributes\FilamentField;

#[FilamentField('Fixtures\App\Models\Email', field: 'latestEmail')]
class FieldAnnotated {}
