<?php

namespace ImSuperlative\FilamentPhpstan\Collectors;

final class AggregateFieldRegistry
{
    /** @var array<string, array<string, array{string, ?string}>> scope → fieldName → [relation, ?column] */
    protected array $overrides = [];

    /** @param array{string, ?string} $parts [relation, ?column] */
    public function register(string $scope, string $fieldName, array $parts): void
    {
        $this->overrides[$scope][$fieldName] = $parts;
    }

    /** @return array{string, ?string}|null [relation, ?column] */
    public function get(string $scope, string $fieldName): ?array
    {
        return $this->overrides[$scope][$fieldName] ?? null;
    }
}
