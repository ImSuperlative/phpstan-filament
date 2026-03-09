<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Rules\ClosureInjection;

use PHPStan\Type\Type;

final readonly class InjectionParameter
{
    public function __construct(
        public string $name,
        public Type $type,
    ) {}
}
