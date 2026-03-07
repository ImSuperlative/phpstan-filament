<?php

/** @noinspection ClassConstantCanBeUsedInspection */

namespace ImSuperlative\FilamentPhpstan\Collectors;

use ImSuperlative\FilamentPhpstan\Support\AstHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<MethodCall, string>
 */
final class RecordsTableCollector implements Collector
{
    public function __construct(
        protected readonly VirtualFieldRegistry $registry,
    ) {}

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): ?string
    {
        if (! $this->shouldProcess($node, $scope)) {
            return null;
        }

        $scopeKey = AstHelper::buildScopeKey($scope);

        if ($scopeKey === null) {
            return null;
        }

        $this->registry->registerSkippedScope($scopeKey);

        return $scopeKey;
    }

    /** @phpstan-assert-if-true Identifier $node->name */
    protected function shouldProcess(MethodCall $node, Scope $scope): bool
    {
        return $node->name instanceof Identifier
            && $node->name->toString() === 'records'
            && in_array('Filament\Tables\Table', $scope->getType($node->var)->getObjectClassNames(), true);
    }
}
