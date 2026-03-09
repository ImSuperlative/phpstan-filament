<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\Handlers;

use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureParameterHandler;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

final class OperationLiteralHandler implements ClosureParameterHandler
{
    public function __construct(
        protected readonly bool $operationLiteral,
    ) {}

    public function resolveType(string $paramName, bool $hasTypeHint, ClosureHandlerContext $context, ?Type $mapType): ?Type
    {
        if (! $this->shouldResolveType($paramName, $hasTypeHint)) {
            return null;
        }

        return new UnionType([
            new ConstantStringType('create'),
            new ConstantStringType('edit'),
            new ConstantStringType('view'),
        ]);
    }

    protected function shouldResolveType(string $paramName, bool $hasTypeHint): bool
    {
        return $this->operationLiteral
            && ! $hasTypeHint
            && ($paramName === 'operation' || $paramName === 'context');
    }
}
