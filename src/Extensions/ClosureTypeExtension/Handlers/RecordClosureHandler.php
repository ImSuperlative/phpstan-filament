<?php

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\Handlers;

use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureParameterHandler;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class RecordClosureHandler implements ClosureParameterHandler
{
    public function __construct(
        protected readonly bool $recordClosure,
        protected readonly FilamentClassHelper $filamentClassHelper,
    ) {}

    public function resolveType(string $paramName, bool $hasTypeHint, ClosureHandlerContext $context, ?Type $mapType): ?Type
    {
        if (! $this->shouldResolveType($paramName, $hasTypeHint, $context)) {
            return null;
        }

        $base = new ObjectType($context->modelClass);
        if (
            $context->callerClass !== null
            && $this->filamentClassHelper->isTableColumn($context->callerClass)
        ) {
            return $base;
        }

        return TypeCombinator::addNull($base);
    }

    protected const array NARROW_PARAMS = ['record', 'replica'];

    /** @phpstan-assert-if-true !null $context->modelClass */
    protected function shouldResolveType(string $paramName, bool $hasTypeHint, ClosureHandlerContext $context): bool
    {
        return $this->recordClosure
            && ! $hasTypeHint
            && in_array($paramName, self::NARROW_PARAMS, true)
            && $context->modelClass !== null;
    }
}
