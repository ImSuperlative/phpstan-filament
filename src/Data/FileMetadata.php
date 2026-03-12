<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data;

final readonly class FileMetadata
{
    /**
     * @param  list<string>  $traits
     * @param  array<string, string>  $useMap
     */
    public function __construct(
        public string $fullyQualifiedName,
        public ?string $extends,
        public array $traits,
        public array $useMap,
        public ?string $namespace,
        public bool $isTrait,
    ) {}
}
