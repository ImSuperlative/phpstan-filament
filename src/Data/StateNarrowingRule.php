<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Data;

use PHPStan\Type\Type;

final readonly class StateNarrowingRule
{
    public function __construct(
        public string $componentClass,
        public string $methodName,
        public Type $narrowedType,
    ) {}

    public function matches(string $componentClass, string $methodName): bool
    {
        return $this->componentClass === $componentClass
            && $this->methodName === $methodName;
    }

    /**
     * @param  list<string>  $methodCalls
     */
    public function matchesAny(string $componentClass, array $methodCalls): bool
    {
        return $this->componentClass === $componentClass
            && in_array($this->methodName, $methodCalls, true);
    }
}
