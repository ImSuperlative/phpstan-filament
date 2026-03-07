<?php

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;

final readonly class ClosureHandlerContext
{
    public function __construct(
        public Scope $scope,
        public MethodCall $methodCall,
        public ?string $modelClass,
        public ?string $callerClass,
        public ?string $declaringClass,
    ) {}
}
