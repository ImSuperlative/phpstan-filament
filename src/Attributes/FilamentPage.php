<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Attributes;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class FilamentPage
{
    /** @var list<string> */
    public readonly array $type;

    /** @param string|list<string> $type */
    public function __construct(
        string|array $type,
        public readonly ?string $model = null,
    ) {
        $this->type = (array) $type;
    }
}
