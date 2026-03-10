<?php

namespace Fixtures\App\Attributes;

use ImSuperlative\PhpstanFilament\Attributes\FilamentState;

#[FilamentState('Carbon\Carbon', field: 'updated_at')]
#[FilamentState('Carbon\Carbon', field: 'created_at')]
class StateAnnotated {}
