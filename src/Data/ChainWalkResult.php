<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data;

final readonly class ChainWalkResult
{
    /** @param list<string> $methodNames */
    public function __construct(
        public array $methodNames,
        public ?string $fieldName,
    ) {}
}
