<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data;

use PHPStan\Type\Type;

class FieldPathResult
{
    /**
     * @param  list<ResolvedSegment>  $segments
     * @param  list<string>  $remaining
     */
    public function __construct(
        public readonly string $modelClass,
        public readonly array $segments,
        public readonly array $remaining,
    ) {}

    public function isFullyResolved(): bool
    {
        if ($this->remaining !== []) {
            return false;
        }

        return $this->firstUnresolved() === null;
    }

    public function leafType(): ?Type
    {
        if (! $this->isFullyResolved()) {
            return null;
        }

        return ($this->segments[count($this->segments) - 1] ?? null)?->type;
    }

    public function lastResolvedClass(): ?string
    {
        for ($i = count($this->segments) - 1; $i >= 0; $i--) {
            if ($this->segments[$i]->resolvedClass !== null) {
                return $this->segments[$i]->resolvedClass;
            }
        }

        return null;
    }

    public function firstUnresolved(): ?ResolvedSegment
    {
        return array_find($this->segments, fn (ResolvedSegment $s) => $s->tags === []);
    }
}
