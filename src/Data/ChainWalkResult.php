<?php

namespace ImSuperlative\FilamentPhpstan\Data;

final readonly class ChainWalkResult
{
    /** @param list<string> $methodNames */
    public function __construct(
        public array $methodNames,
        public ?string $fieldName,
    ) {}
}
