<?php

namespace ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;

final readonly class ClosureHandlerContext
{
    /**
     * @param  list<string>  $modelClasses
     */
    public function __construct(
        public Scope $scope,
        public MethodCall $methodCall,
        public array $modelClasses,
        public ?string $callerClass,
        public ?string $declaringClass,
    ) {}

    /**
     * Returns the single model class when unambiguous, null otherwise.
     */
    public function modelClass(): ?string
    {
        return count($this->modelClasses) === 1 ? $this->modelClasses[0] : null;
    }
}
