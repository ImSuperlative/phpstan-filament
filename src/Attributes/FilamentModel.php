<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Attributes;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class FilamentModel
{
    /** @var list<string> */
    public readonly array $type;

    /** @param string|list<string> $type */
    public function __construct(string|array $type)
    {
        $this->type = (array) $type;
    }
}
