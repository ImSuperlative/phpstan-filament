<?php

namespace Fixtures\App\Attributes;

use ImSuperlative\PhpstanFilament\Attributes\FilamentState;

#[FilamentState('Carbon\Carbon|null', field: 'deleted_at')]
class StateWithTypeExpression {}
