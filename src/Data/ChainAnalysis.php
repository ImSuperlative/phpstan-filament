<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data;

final readonly class ChainAnalysis
{
    /**
     * @param  list<string>  $methodCalls  All method names found in the chain
     * @param  list<string|int>|null  $literalOptionKeys  Keys from a literal ->options([...]) array
     */
    public function __construct(
        public ?string $componentClass,
        public array $methodCalls,
        public ?string $enumClass,
        public ?array $literalOptionKeys,
        public bool $isMultiple,
        public bool $isRequired,
        public ?string $fieldName,
    ) {}
}
