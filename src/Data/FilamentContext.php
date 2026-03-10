<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data;

final readonly class FilamentContext
{
    public function __construct(
        public ?string $componentClass = null,
        public ?string $resourceClass = null,
        public ?string $modelClass = null,
        public bool $isNested = false,
    ) {}

    public function hasModelContext(): bool
    {
        return $this->modelClass !== null;
    }

    public function hasComponentContext(): bool
    {
        return $this->componentClass !== null;
    }

    public function hasResourceContext(): bool
    {
        return $this->resourceClass !== null;
    }
}
