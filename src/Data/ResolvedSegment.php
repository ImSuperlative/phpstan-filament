<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data;

use PHPStan\Type\Type;

class ResolvedSegment
{
    /**
     * @param  list<SegmentTag>  $tags
     */
    public function __construct(
        public readonly string $name,
        public readonly array $tags,
        public readonly ?string $resolvedClass,
        public readonly ?Type $type,
    ) {}

    public function is(SegmentTag $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }

    public function isAny(SegmentTag ...$tags): bool
    {
        return array_any($tags, fn (SegmentTag $tag) => $this->is($tag));
    }
}
