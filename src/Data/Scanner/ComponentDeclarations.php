<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

final readonly class ComponentDeclarations
{
    /** @use HasTypedMap<class-string, ComponentDeclaration> */
    use HasTypedMap;

    /** @param array<class-string, ComponentDeclaration> $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
