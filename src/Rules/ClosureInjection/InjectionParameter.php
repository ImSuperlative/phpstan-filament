<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Rules\ClosureInjection;

use PHPStan\Type\Type;

final readonly class InjectionParameter
{
    public function __construct(
        public string $name,
        public Type $type,
    ) {}
}
