<?php

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\Handlers;

use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureParameterHandler;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

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

        return new GenericObjectType(
            'Illuminate\Database\Eloquent\Collection',
            [
                new IntegerType,
                new ObjectType($context->modelClass),
            ],
        );
    }

    /** @phpstan-assert-if-true !null $context->modelClass */
    protected function shouldResolveType(string $paramName, ClosureHandlerContext $context): bool
    {
        return $this->actionRecords
            && $context->modelClass !== null
            && ($paramName === 'records' || $paramName === 'selectedRecords');
    }
}
