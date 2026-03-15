<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\Handlers;

use ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\ClosureParameterHandler;
use ImSuperlative\PhpstanFilament\Scanner\FilamentProjectIndex;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class LivewireClosureHandler implements ClosureParameterHandler
{
    public function __construct(
        protected readonly FilamentClassHelper $filamentClassHelper,
        protected readonly FilamentProjectIndex $projectIndex,
    ) {}

    public function resolveType(string $paramName, bool $hasTypeHint, ClosureHandlerContext $context, ?Type $mapType): ?Type
    {
        if ($hasTypeHint || $paramName !== 'livewire' || $mapType === null) {
            return null;
        }

        $classReflection = $context->scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $schemaClass = $classReflection->getName();

        if ($this->filamentClassHelper->isResourceScoped($schemaClass)) {
            return new ObjectType($schemaClass);
        }

        $component = $this->projectIndex->getComponent($schemaClass);
        $pages = $component?->getPages() ?? [];

        return $pages !== [] ? $this->buildUnionType($pages) : null;
    }

    /** @param list<string> $classNames */
    protected function buildUnionType(array $classNames): Type
    {
        $types = array_map(fn (string $class) => new ObjectType($class), $classNames);

        return count($types) === 1 ? $types[0] : TypeCombinator::union(...$types);
    }
}
