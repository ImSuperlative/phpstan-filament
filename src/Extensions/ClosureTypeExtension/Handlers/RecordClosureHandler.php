<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\Handlers;

use ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\ClosureParameterHandler;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class RecordClosureHandler implements ClosureParameterHandler
{
    protected const array NARROW_PARAMS = ['record', 'replica'];

    public function __construct(
        protected readonly bool $recordClosure,
        protected readonly FilamentClassHelper $filamentClassHelper,
    ) {}

    public function resolveType(string $paramName, bool $hasTypeHint, ClosureHandlerContext $context, ?Type $mapType): ?Type
    {
        if (! $this->shouldResolveType($paramName, $hasTypeHint, $context)) {
            return null;
        }

        $base = TypeCombinator::union(
            ...array_map(
                fn (string $class) => new ObjectType($class),
                $context->modelClasses,
            ),
        );

        if (
            $context->callerClass !== null
            && $this->filamentClassHelper->isTableColumn($context->callerClass)
        ) {
            return $base;
        }

        return TypeCombinator::addNull($base);
    }

    protected function shouldResolveType(string $paramName, bool $hasTypeHint, ClosureHandlerContext $context): bool
    {
        return $this->recordClosure
            && ! $hasTypeHint
            && in_array($paramName, self::NARROW_PARAMS, true)
            && $context->modelClasses !== [];
    }
}
