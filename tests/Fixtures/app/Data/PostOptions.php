<?php

namespace Fixtures\App\Data;

use Illuminate\Support\Collection;

class PostOptions
{
    public function __construct(
        public bool $is_pinned = false,
        public ?PostMeta $meta = null,
        /** @var Collection<int, PostMeta>|null */
        public ?Collection $items = null,
    ) {}
}
