<?php

namespace ImSuperlative\FilamentPhpstan\Collectors;

use ImSuperlative\FilamentPhpstan\Support\AstHelper;
use ImSuperlative\FilamentPhpstan\Support\FilamentClassHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<MethodCall, array{string, string, array{string, ?string}}>
 */
final class AggregateOverrideCollector implements Collector
{
    /** Methods that take (relationship) */
    protected const array COUNT_METHODS = ['counts', 'exists'];

    /** Methods that take (relationship, column) */
    protected const array COLUMN_METHODS = ['avg', 'max', 'min', 'sum'];

    public function __construct(
        protected readonly AggregateFieldRegistry $registry,
        protected readonly FilamentClassHelper $filamentClassHelper,
    ) {}

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param  MethodCall  $node
     * @return array{string, string, array{string, ?string}}|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (! $this->shouldProcess($node, $scope)) {
            return null;
        }

        $parts = $this->extractParts($node->name->toString(), $node);
        if ($parts === null) {
            return null;
        }

        $fieldName = AstHelper::walkMethodChain($node->var, function () {})->fieldName;
        $scopeKey = AstHelper::buildScopeKey($scope);

        if ($fieldName === null || $scopeKey === null) {
            return null;
        }

        $this->registry->register($scopeKey, $fieldName, $parts);

        return [$scopeKey, $fieldName, $parts];
    }

    /** @phpstan-assert-if-true Identifier $node->name */
    protected function shouldProcess(MethodCall $node, Scope $scope): bool
    {
        if (! $node->name instanceof Identifier) {
            return false;
        }

        $methodName = $node->name->toString();
        if (! in_array($methodName, self::COUNT_METHODS, true) && ! in_array($methodName, self::COLUMN_METHODS, true)) {
            return false;
        }

        $callerClass = $scope->getType($node->var)->getObjectClassNames()[0] ?? null;

        return $callerClass !== null && $this->filamentClassHelper->isDisplayComponent($callerClass);
    }

    /** @return array{string, ?string}|null [relation, ?column] */
    protected function extractParts(string $methodName, MethodCall $node): ?array
    {
        $args = $node->getArgs();

        if (in_array($methodName, self::COUNT_METHODS, true)) {
            $relation = ($args[0] ?? null)?->value;

            return $relation instanceof String_ ? [$relation->value, null] : null;
        }

        if (in_array($methodName, self::COLUMN_METHODS, true)) {
            $relation = ($args[0] ?? null)?->value;
            $column = ($args[1] ?? null)?->value;

            return $relation instanceof String_
                ? [$relation->value, $column instanceof String_ ? $column->value : null]
                : null;
        }

        return null;
    }
}
