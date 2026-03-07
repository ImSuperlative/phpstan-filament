<?php

namespace ImSuperlative\FilamentPhpstan;

enum FieldValidationLevel: int
{
    case Level_0 = 0;
    case Level_1 = 1;
    case Level_2 = 2;
    case Level_3 = 3;

    public const self Off = self::Level_0;

    public const self RelationsOnly = self::Level_1;

    public const self Strict = self::Level_2;

    public const self Full = self::Level_3;

    public function isEnabled(): bool
    {
        return $this !== self::Off;
    }

    public function shouldValidatePlainFields(): bool
    {
        return $this->value >= 2;
    }

    public function shouldErrorOnUnknownSegment(): bool
    {
        return $this->value >= 2;
    }

    public function shouldWalkTypedProperties(): bool
    {
        return $this->value >= 3;
    }

    public function shouldValidateLeaf(): bool
    {
        return $this->value >= 3;
    }

    public function shouldValidateAggregateColumn(): bool
    {
        return $this->value >= 3;
    }
}
