<?php

if (! function_exists('array_find_map')) {
    /**
     * Returns the first non-null result of applying the callback to each element.
     *
     * @template T
     * @template R
     *
     * @param  array<T>  $items
     * @param  callable(T): ?R  $callback
     * @return ?R
     */
    function array_find_map(array $items, callable $callback): mixed
    {
        foreach ($items as $item) {
            $result = $callback($item);

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}
