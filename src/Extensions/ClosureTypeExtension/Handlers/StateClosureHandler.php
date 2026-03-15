<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\Handlers;

use ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\ClosureHandlerContext;
use ImSuperlative\PhpstanFilament\Extensions\ClosureTypeExtension\ClosureParameterHandler;
use ImSuperlative\PhpstanFilament\Resolvers\StateTypeResolver;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class StateClosureHandler implements ClosureParameterHandler
{
    public function __construct(
        protected readonly bool $typeClosures,
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
        return $this->typeClosures
            && ! $hasTypeHint
            && ($paramName === 'state' || $paramName === 'old' || $paramName === 'oldRaw');
    }
}
