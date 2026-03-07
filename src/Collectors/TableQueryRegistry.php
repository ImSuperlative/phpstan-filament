<?php

namespace ImSuperlative\FilamentPhpstan\Collectors;

final class TableQueryRegistry
{
    /** @var array<string, string> "ClassName::methodName" → model FQCN */
    protected array $entries = [];

    public function register(string $className, string $methodName, string $modelClass): void
    {
        $this->entries[$className.'::'.$methodName] = $modelClass;
    }

    public function lookup(string $className, string $methodName): ?string
    {
        return $this->entries[$className.'::'.$methodName] ?? null;
    }
}
