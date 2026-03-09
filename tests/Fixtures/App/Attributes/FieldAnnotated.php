<?php

namespace Fixtures\App\Attributes;

use ImSuperlative\FilamentPhpstan\Attributes\FilamentField;

#[FilamentField('Fixtures\App\Models\Email', field: 'latestEmail')]
class FieldAnnotated {}
