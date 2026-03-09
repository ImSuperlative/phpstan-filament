<?php

namespace ImSuperlative\FilamentPhpstan\Attributes;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class FilamentField
{
    /** @var list<string> */
    public readonly array $type;

    /** @param string|list<string> $type */
    public function __construct(
        string|array $type,
        public readonly ?string $field = null,
    ) {
        $this->type = (array) $type;
    }
}
