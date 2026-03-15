<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\Handlers;

use ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\ClosureParameterHandler;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

final class OperationLiteralHandler implements ClosureParameterHandler
{
    public function __construct(
        protected readonly bool $typeClosures,
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
        return $this->typeClosures
            && ! $hasTypeHint
            && ($paramName === 'operation' || $paramName === 'context');
    }
}
