<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner;

use ImSuperlative\PhpstanFilament\Data\FileMetadata;

final class ProjectScanResult
{
    /** @var array<class-string, object> */
    protected array $attributes = [];

    /**
     * @param  array<string, FileMetadata>  $index  filePath => metadata
     * @param  list<string>  $roots  file paths of resource roots
     */
    public function __construct(
        public readonly array $index,
        public readonly array $roots = [],
    ) {}

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T|null
     */
    public function get(string $class): ?object
    {
        /** @var T|null */
        return $this->attributes[$class] ?? null;
    }

    /** @param  class-string  $class */
    public function has(string $class): bool
    {
        return isset($this->attributes[$class]);
    }

    public function set(object $attribute): self
    {
        $this->attributes[$attribute::class] = $attribute;

        return $this;
    }
}
