<?php

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\Handlers;

use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureParameterHandler;
use ImSuperlative\FilamentPhpstan\Resolvers\StateTypeResolver;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class StateClosureHandler implements ClosureParameterHandler
{
    public function __construct(
        protected readonly bool $stateClosure,
        protected readonly StateTypeResolver $stateTypeResolver,
    ) {}

    public function resolveType(string $paramName, bool $hasTypeHint, ClosureHandlerContext $context, ?Type $mapType): ?Type
    {
        if (! $this->shouldResolveType($paramName, $hasTypeHint)) {
            return null;
        }

        if ($context->modelClasses === []) {
            return $this->stateTypeResolver->resolve($context->methodCall, $context->scope, null);
        }

        $types = [];

        foreach ($context->modelClasses as $modelClass) {
            $type = $this->stateTypeResolver->resolve($context->methodCall, $context->scope, $modelClass);

            if ($type !== null) {
                $types[] = $type;
            }
        }

        return $types !== [] ? TypeCombinator::union(...$types) : null;
    }

    protected function shouldResolveType(string $paramName, bool $hasTypeHint): bool
    {
        return $this->stateClosure
            && ! $hasTypeHint
            && ($paramName === 'state' || $paramName === 'old' || $paramName === 'oldRaw');
    }
}
