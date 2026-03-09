<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\Handlers;

use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureParameterHandler;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class ActionRecordsHandler implements ClosureParameterHandler
{
    public function __construct(
        protected readonly bool $actionRecords,
    ) {}

    public function resolveType(string $paramName, bool $hasTypeHint, ClosureHandlerContext $context, ?Type $mapType): ?Type
    {
        if (! $this->shouldResolveType($paramName, $context)) {
            return null;
        }

        return TypeCombinator::union(
            ...array_map(
                fn (string $class) => new GenericObjectType(
                    'Illuminate\Database\Eloquent\Collection',
                    [
                        new IntegerType,
                        new ObjectType($class),
                    ],
                ),
                $context->modelClasses,
            ),
        );
    }

    protected function shouldResolveType(string $paramName, ClosureHandlerContext $context): bool
    {
        return $this->actionRecords
            && $context->modelClasses !== []
            && ($paramName === 'records' || $paramName === 'selectedRecords');
    }
}
