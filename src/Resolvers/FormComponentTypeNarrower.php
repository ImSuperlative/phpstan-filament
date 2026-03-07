<?php

/** @noinspection ClassConstantCanBeUsedInspection */

namespace ImSuperlative\FilamentPhpstan\Resolvers;

use ImSuperlative\FilamentPhpstan\Data\ChainAnalysis;
use ImSuperlative\FilamentPhpstan\Data\StateNarrowingRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class FormComponentTypeNarrower
{
    public function __construct(
        protected readonly ReflectionProvider $reflectionProvider,
        protected readonly FormOptionsNarrower $optionsNarrower,
    ) {}

    /** @return list<StateNarrowingRule> */
    protected function rules(): array
    {
        return [
            new StateNarrowingRule('Filament\Forms\Components\TextInput', 'numeric', TypeCombinator::addNull(new FloatType)),
            new StateNarrowingRule('Filament\Forms\Components\TextInput', 'integer', TypeCombinator::addNull(new FloatType)),
            new StateNarrowingRule('Filament\Forms\Components\Select', 'multiple', new ArrayType(new IntegerType, TypeCombinator::union(new StringType, new IntegerType))),
            new StateNarrowingRule('Filament\Forms\Components\FileUpload', 'multiple', TypeCombinator::addNull(new ArrayType(new IntegerType, new StringType))),
            new StateNarrowingRule('Filament\Forms\Components\Radio', 'boolean', TypeCombinator::addNull(new IntegerType)),
            new StateNarrowingRule('Filament\Forms\Components\ToggleButtons', 'multiple', new ArrayType(new IntegerType, TypeCombinator::union(new StringType, new IntegerType))),
        ];
    }

    /**
     * @param  list<string>  $methodCalls
     */
    public function narrow(string $componentClass, Type $baseType, array $methodCalls): Type
    {
        return $this->matchRules($componentClass, $methodCalls) ?? $baseType;
    }

    /**
     * @param  list<string>  $methodCalls
     */
    public function narrowForClass(string $className, Type $baseType, array $methodCalls): Type
    {
        return array_find_map(
            $this->classHierarchy($className),
            fn ($candidate) => $this->matchRules($candidate, $methodCalls),
        ) ?? $baseType;
    }

    public function narrowWithOptions(ChainAnalysis $analysis, Type $baseType): Type
    {
        // Priority 1: Enum narrowing (e.g. ->enum(PostStatus::class))
        // Priority 2: Literal options array narrowing (e.g. ->options(['draft' => 'Draft']))
        // Priority 3: Method-based narrowing

        return $this->narrowFromEnum($analysis)
            ?? $this->narrowFromLiteralOptions($analysis)
            ?? $this->narrow($analysis->componentClass ?? '', $baseType, $analysis->methodCalls);
    }

    /** @return list<string> */
    protected function classHierarchy(string $className): array
    {
        return $this->reflectionProvider->hasClass($className)
            ? [$className, ...$this->reflectionProvider->getClass($className)->getParentClassesNames()]
            : [$className];
    }

    /**
     * @param  list<string>  $methodCalls
     */
    protected function matchRules(string $className, array $methodCalls): ?Type
    {
        return array_reduce(
            $this->rules(),
            fn (?Type $carry, StateNarrowingRule $rule) => $rule->matchesAny($className, $methodCalls)
                ? $rule->narrowedType
                : $carry,
        );
    }

    protected function narrowFromEnum(ChainAnalysis $analysis): ?Type
    {
        return $analysis->enumClass !== null
            ? $this->optionsNarrower->fromEnum($analysis->enumClass, $analysis->isMultiple)
            : null;
    }

    protected function narrowFromLiteralOptions(ChainAnalysis $analysis): ?Type
    {
        return $analysis->literalOptionKeys !== null
            ? $this->optionsNarrower->fromLiteralOptions($analysis->literalOptionKeys, $analysis->isMultiple)
            : null;
    }
}
