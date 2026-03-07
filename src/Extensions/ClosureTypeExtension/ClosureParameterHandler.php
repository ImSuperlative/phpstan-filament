<?php

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension;

use PHPStan\Type\Type;

interface ClosureParameterHandler
{
    /**
     * @param  ?Type  $mapType  The type resolved from TypedInjectionMap (null if param not in map)
     */
    public function resolveType(string $paramName, bool $hasTypeHint, ClosureHandlerContext $context, ?Type $mapType): ?Type;
}
