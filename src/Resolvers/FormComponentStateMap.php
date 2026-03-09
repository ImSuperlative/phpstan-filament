<?php

/** @noinspection ClassConstantCanBeUsedInspection */

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Resolvers;

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
            'Filament\Forms\Components\TextInput' => TypeCombinator::addNull(new StringType),
            'Filament\Forms\Components\Textarea' => TypeCombinator::addNull(new StringType),
            'Filament\Forms\Components\RichEditor' => TypeCombinator::addNull(new StringType),
            'Filament\Forms\Components\MarkdownEditor' => TypeCombinator::addNull(new StringType),
            'Filament\Forms\Components\Select' => TypeCombinator::addNull(TypeCombinator::union(new StringType, new IntegerType)),
            'Filament\Forms\Components\Toggle' => TypeCombinator::addNull(new BooleanType),
            'Filament\Forms\Components\Checkbox' => TypeCombinator::addNull(new BooleanType),
            'Filament\Forms\Components\CheckboxList' => new ArrayType(new IntegerType, TypeCombinator::union(new StringType, new IntegerType)),
            'Filament\Forms\Components\Radio' => TypeCombinator::addNull(TypeCombinator::union(new StringType, new IntegerType)),
            'Filament\Forms\Components\ToggleButtons' => TypeCombinator::addNull(TypeCombinator::union(new StringType, new IntegerType)),
            'Filament\Forms\Components\DatePicker' => TypeCombinator::addNull(new StringType),
            'Filament\Forms\Components\DateTimePicker' => TypeCombinator::addNull(new StringType),
            'Filament\Forms\Components\TimePicker' => TypeCombinator::addNull(new StringType),
            'Filament\Forms\Components\ColorPicker' => TypeCombinator::addNull(new StringType),
            'Filament\Forms\Components\FileUpload' => TypeCombinator::addNull(new StringType),
            'Filament\Forms\Components\KeyValue' => TypeCombinator::addNull(new ArrayType(new StringType, new StringType)),
            'Filament\Forms\Components\Repeater' => TypeCombinator::addNull(new ArrayType(new MixedType, new MixedType)),
            'Filament\Forms\Components\Builder' => TypeCombinator::addNull(new ArrayType(new MixedType, new MixedType)),
            'Filament\Forms\Components\TagsInput' => TypeCombinator::addNull(new ArrayType(new IntegerType, new StringType)),
            'Filament\Forms\Components\Hidden' => new MixedType,
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
