<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Resolvers;

use ImSuperlative\PhpstanFilament\Support\FilamentComponent as FC;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class FormComponentStateMap
{
    /** @var array<string, Type>|null */
    protected static ?array $map = null;

    public function __construct(
        protected readonly ReflectionProvider $reflectionProvider,
    ) {}

    /** @return array<string, Type> */
    protected static function map(): array
    {
        return self::$map ??= [
            FC::TEXT_INPUT => TypeCombinator::addNull(new StringType),
            FC::TEXTAREA => TypeCombinator::addNull(new StringType),
            FC::RICH_EDITOR => TypeCombinator::addNull(new StringType),
            FC::MARKDOWN_EDITOR => TypeCombinator::addNull(new StringType),
            FC::SELECT => TypeCombinator::addNull(TypeCombinator::union(new StringType, new IntegerType)),
            FC::TOGGLE => TypeCombinator::addNull(new BooleanType),
            FC::CHECKBOX => TypeCombinator::addNull(new BooleanType),
            FC::CHECKBOX_LIST => new ArrayType(new IntegerType, TypeCombinator::union(new StringType, new IntegerType)),
            FC::RADIO => TypeCombinator::addNull(TypeCombinator::union(new StringType, new IntegerType)),
            FC::TOGGLE_BUTTONS => TypeCombinator::addNull(TypeCombinator::union(new StringType, new IntegerType)),
            FC::DATE_PICKER => TypeCombinator::addNull(new StringType),
            FC::DATE_TIME_PICKER => TypeCombinator::addNull(new StringType),
            FC::TIME_PICKER => TypeCombinator::addNull(new StringType),
            FC::COLOR_PICKER => TypeCombinator::addNull(new StringType),
            FC::FILE_UPLOAD => TypeCombinator::addNull(new StringType),
            FC::KEY_VALUE => TypeCombinator::addNull(new ArrayType(new StringType, new StringType)),
            FC::REPEATER => TypeCombinator::addNull(new ArrayType(new MixedType, new MixedType)),
            FC::BUILDER => TypeCombinator::addNull(new ArrayType(new MixedType, new MixedType)),
            FC::TAGS_INPUT => TypeCombinator::addNull(new ArrayType(new IntegerType, new StringType)),
            FC::HIDDEN => new MixedType,
        ];
    }

    public function resolveForClass(string $className): ?Type
    {
        return $this->resolve($className)
            ?? $this->resolveFromParents($className);
    }

    public function resolve(string $className): ?Type
    {
        return self::map()[$className] ?? null;
    }

    protected function resolveFromParents(string $className): ?Type
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $parents = $this->reflectionProvider->getClass($className)->getParentClassesNames();
        $firstMatch = array_find($parents, fn (string $parent) => isset(self::map()[$parent]));

        return $firstMatch !== null ? self::map()[$firstMatch] : null;
    }
}
