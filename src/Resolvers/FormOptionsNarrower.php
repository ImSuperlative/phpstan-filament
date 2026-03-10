<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Resolvers;

use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionEnum;
use ReflectionEnumBackedCase;

final class FormOptionsNarrower
{
    /**
     * Narrow state type when ->enum(EnumClass::class) is used.
     */
    public function fromEnum(string $enumClass, bool $isMultiple): ?Type
    {
        $reflection = $this->resolveBackedEnum($enumClass);

        if ($reflection === null) {
            return null;
        }

        return $this->wrapForCardinality(new ObjectType($enumClass), $isMultiple);
    }

    /**
     * Narrow state type when ->options(['key' => 'Label', ...]) has literal keys.
     *
     * @param  list<string|int>  $keys
     */
    public function fromLiteralOptions(array $keys, bool $isMultiple): ?Type
    {
        if ($keys === []) {
            return null;
        }

        $literalTypes = array_map(
            fn (string|int $key): Type => is_string($key)
                ? new ConstantStringType($key)
                : new ConstantIntegerType($key),
            $keys,
        );

        $union = TypeCombinator::union(...$literalTypes);

        return $this->wrapForCardinality($union, $isMultiple);
    }

    /**
     * Resolve a backed enum's case values as a literal union type.
     */
    public function enumValuesAsLiteralUnion(string $enumClass, bool $isMultiple): ?Type
    {
        $reflection = $this->resolveBackedEnum($enumClass);

        if ($reflection === null) {
            return null;
        }

        /** @var list<ReflectionEnumBackedCase> $cases */
        $cases = $reflection->getCases();

        $values = array_map(
            fn (ReflectionEnumBackedCase $case): string|int => $case->getBackingValue(),
            $cases,
        );

        return $this->fromLiteralOptions($values, $isMultiple);
    }

    /** @phpstan-ignore return.unusedType, missingType.generics */
    protected function resolveBackedEnum(string $class): ?ReflectionEnum
    {
        if (! enum_exists($class)) {
            return null;
        }

        $ref = new ReflectionEnum($class);

        return $ref->isBacked() ? $ref : null;
    }

    protected function wrapForCardinality(Type $type, bool $isMultiple): Type
    {
        return $isMultiple
            ? new ArrayType(new IntegerType, $type)
            : TypeCombinator::addNull($type);
    }
}
