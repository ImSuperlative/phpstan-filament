<?php

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\Handlers;

use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\ClosureParameterHandler;
use ImSuperlative\FilamentPhpstan\Resolvers\StateTypeResolver;
use PHPStan\Type\Type;

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

        return $this->stateTypeResolver->resolve($context->methodCall, $context->scope, $context->modelClass);
    }

    protected function shouldResolveType(string $paramName, bool $hasTypeHint): bool
    {
        return $this->stateClosure
            && ! $hasTypeHint
            && ($paramName === 'state' || $paramName === 'old' || $paramName === 'oldRaw');
    }
}
