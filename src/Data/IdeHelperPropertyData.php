<?php

namespace ImSuperlative\FilamentPhpstan\Data;

use PHPStan\Type\Type;

final class IdeHelperPropertyData
{
    public function __construct(
        public readonly string $name,
        public readonly Type $type,
        public readonly bool $readOnly = false,
    ) {}
}
