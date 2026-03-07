<?php

namespace ImSuperlative\FilamentPhpstan\Collectors;

use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<StaticCall, array{string, string}>
 */
final class CustomComponentCollector implements Collector
{
    public function __construct(
        protected readonly CustomComponentRegistry $registry,
        protected readonly FilamentClassHelper $filamentClassHelper,
        protected readonly ComponentContextResolver $componentContextResolver,
    ) {}

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param  StaticCall  $node
     * @return array{string, string}|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (! $this->shouldProcess($node, $scope)) {
            return null;
        }

        $modelClass = $this->componentContextResolver->resolveModelClassFromScope($scope);

        if ($modelClass === null) {
            return null;
        }

        $calledClass = $scope->resolveName($node->class);
        $this->registry->register($calledClass, $modelClass);

        return [$calledClass, $modelClass];
    }

    /**
     * @phpstan-assert-if-true Identifier $node->name
     * @phpstan-assert-if-true Name $node->class
     */
    protected function shouldProcess(StaticCall $node, Scope $scope): bool
    {
        if (! $node->name instanceof Identifier || $node->name->name !== 'make' || ! $node->class instanceof Name) {
            return false;
        }

        // Skip Filament components — they're already handled by MakeFieldValidationRule
        return ! $this->filamentClassHelper->isFilamentComponent($scope->resolveName($node->class));
    }
}
