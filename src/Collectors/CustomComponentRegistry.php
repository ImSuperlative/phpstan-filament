<?php

namespace ImSuperlative\FilamentPhpstan\Collectors;

final class CustomComponentRegistry
{
    /** @var array<string, string> helperClass => modelClass */
    protected array $models = [];

    public function register(string $helperClass, string $modelClass): void
    {
        $this->models[$helperClass] = $modelClass;
    }

    public function getModelForClass(string $className): ?string
    {
        return $this->models[$className] ?? null;
    }
}
