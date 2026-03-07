<?php

namespace ImSuperlative\FilamentPhpstan\Collectors;

final class VirtualFieldRegistry
{
    /** @var array<string, array<string, true>> "ClassName::methodName" → set of virtual field names */
    protected array $virtualFields = [];

    /** @var array<string, true> "ClassName::methodName" → true */
    protected array $skippedScopes = [];

    public function registerVirtual(string $scope, string $fieldName): void
    {
        $this->virtualFields[$scope][$fieldName] = true;
    }

    public function registerSkippedScope(string $scope): void
    {
        $this->skippedScopes[$scope] = true;
    }

    public function isVirtual(string $scope, string $fieldName): bool
    {
        return isset($this->virtualFields[$scope][$fieldName]);
    }

    public function isScopeSkipped(string $scope): bool
    {
        return isset($this->skippedScopes[$scope]);
    }
}
