<?php

namespace Fixtures\App\Data;

class PostMeta
{
    public function __construct(
        public string $seo_title = '',
        public string $seo_description = '',
    ) {}
}
