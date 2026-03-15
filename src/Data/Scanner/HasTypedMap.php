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

    /**
     * Get a value by key and wrap it into a provider object.
     *
     * @template TProvider
     *
     * @param  class-string<TProvider>  $providerClass
     * @return TProvider|null
     */
    public function into(string $key, string $providerClass): mixed
    {
        $value = $this->get($key);

        return $value !== null ? new $providerClass($value) : null;
    }

    /**
     * Wrap all values into provider objects.
     *
     * @template TProvider
     *
     * @param  class-string<TProvider>  $providerClass
     * @return array<TKey, TProvider>
     */
    public function mapInto(string $providerClass): array
    {
        return array_map(
            fn ($value) => new $providerClass($value),
            $this->data,
        );
    }

}
