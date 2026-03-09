<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;

final class SimpleParameterReflection implements ParameterReflection
{
    public function __construct(
        protected readonly string $name,
        protected readonly Type $type,
        protected readonly bool $optional = false,
        protected readonly bool $variadic = false,
        protected readonly ?Type $defaultValue = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function passedByReference(): PassedByReference
    {
        return PassedByReference::createNo();
    }

    public function isVariadic(): bool
    {
        return $this->variadic;
    }

    public function getDefaultValue(): ?Type
    {
        return $this->defaultValue;
    }
}
