<?php

namespace ImSuperlative\FilamentPhpstan\Rules\ClosureInjection;

use PHPStan\Type\Type;

final readonly class InjectionParameter
{
    public function __construct(
        public string $name,
        public Type $type,
    ) {}
}
