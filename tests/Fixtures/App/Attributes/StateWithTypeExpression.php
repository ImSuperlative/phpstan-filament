<?php

namespace Fixtures\App\Attributes;

use ImSuperlative\FilamentPhpstan\Attributes\FilamentState;

#[FilamentState('Carbon\Carbon|null', field: 'deleted_at')]
class StateWithTypeExpression {}
