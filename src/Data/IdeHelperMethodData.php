<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Data;

use PHPStan\Type\Type;

final class IdeHelperMethodData
{
    /**
     * @param  list<IdeHelperParameterData>  $parameters
     */
    public function __construct(
        public readonly string $name,
        public readonly Type $returnType,
        public readonly bool $isStatic = false,
        public readonly array $parameters = [],
    ) {}
}
