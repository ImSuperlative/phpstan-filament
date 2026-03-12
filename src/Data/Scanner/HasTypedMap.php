<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data\Scanner;

/**
 * @template TKey of string
 * @template TValue
 */
trait HasTypedMap
{
    /** @var array<TKey, TValue> */
    protected readonly array $data;

    /** @return TValue|null */
    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /** @return array<TKey, TValue> */
    public function all(): array
    {
        return $this->data;
    }
}
