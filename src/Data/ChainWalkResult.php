<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data;

/**
 * @template T
 */
final readonly class ChainWalkResult
{
    /**
     * @param  list<string>  $methodNames
     * @param  list<T>  $visitorResults
     */
    public function __construct(
        public array $methodNames,
        public ?string $fieldName,
        public array $visitorResults = [],
    ) {}
}
