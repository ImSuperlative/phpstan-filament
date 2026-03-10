<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data;

use PHPStan\Type\Type;

final class IdeHelperParameterData
{
    public function __construct(
        public readonly string $name,
        public readonly Type $type,
        public readonly bool $optional = false,
        public readonly ?string $defaultValue = null,
    ) {}
}
