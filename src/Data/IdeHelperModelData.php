<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data;

final class IdeHelperModelData
{
    /**
     * @param  array<string, IdeHelperPropertyData>  $properties
     * @param  array<string, IdeHelperMethodData>  $methods
     */
    public function __construct(
        public readonly string $className,
        public readonly array $properties = [],
        public readonly array $methods = [],
    ) {}
}
