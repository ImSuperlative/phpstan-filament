<?php

declare(strict_types=1);

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

if (! function_exists('project_root')) {
    function project_root(string $path): string
    {
        return realpath(dirname(__DIR__, 1).'/'.ltrim($path, '/')) ?: '';
    }
}

if (! function_exists('tests_path')) {
    function tests_path(string $path): string
    {
        return project_root('tests/'.ltrim($path, '/'));
    }
}

if (! function_exists('fixture_path')) {
    function fixture_path(string $path): string
    {
        return project_root('tests/Fixtures/'.ltrim($path, '/'));
    }
}
