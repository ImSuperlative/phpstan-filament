<?php

namespace ImSuperlative\FilamentPhpstan\Collectors;

use ImSuperlative\FilamentPhpstan\Support\AstHelper;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<MethodCall, array{string, string}>
 */
final class StateOverrideCollector implements Collector
{
    protected const array OVERRIDE_METHODS = ['state', 'getStateUsing', 'view'];

    public function __construct(
        protected readonly VirtualFieldRegistry $registry,
        protected readonly FilamentClassHelper $filamentClassHelper,
    ) {}

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param  MethodCall  $node
     * @return array{string, string}|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (! $this->shouldProcess($node, $scope)) {
            return null;
        }

        $fieldName = AstHelper::walkMethodChain($node->var, function () {})->fieldName;
        $scopeKey = AstHelper::buildScopeKey($scope);

        if ($fieldName === null || $scopeKey === null) {
            return null;
        }

        $this->registry->registerVirtual($scopeKey, $fieldName);

        return [$scopeKey, $fieldName];
    }

    /**
     * @phpstan-assert-if-true Identifier $node->name
     */
    protected function shouldProcess(MethodCall $node, Scope $scope): bool
    {
        if (! $node->name instanceof Identifier) {
            return false;
        }

        $methodName = $node->name->toString();
        $callerClass = $scope->getType($node->var)->getObjectClassNames()[0] ?? null;

        if ($callerClass === null) {
            return false;
        }

        if (in_array($methodName, self::OVERRIDE_METHODS, true)) {
            return $this->filamentClassHelper->isDisplayComponent($callerClass);
        }

        if ($methodName === 'placeholder') {
            return $this->filamentClassHelper->isInfolistEntry($callerClass);
        }

        return false;
    }
}
