<?php

namespace ImSuperlative\PhpstanFilament\Tests;

/** @internal */
final class AssertCollector
{
    /** @var list<mixed[]> */
    protected array $asserts = [];

    public function add(array $assert): void
    {
        $this->asserts[] = $assert;
    }

    /** @return list<mixed[]> */
    public function toArray(): array
    {
        return $this->asserts;
    }
}
