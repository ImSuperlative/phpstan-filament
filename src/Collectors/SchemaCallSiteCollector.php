<?php

namespace ImSuperlative\FilamentPhpstan\Collectors;

use ImSuperlative\FilamentPhpstan\Resolvers\ComponentContextResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects static calls like `FormForm::configure($schema)` from within
 * resource classes to build a schema class → resource → model map.
 *
 * @implements Collector<StaticCall, array{schemaClass: string, callerClass: string}>
 */
final class SchemaCallSiteCollector implements Collector
{
    public function __construct(
        protected readonly SchemaCallSiteRegistry $registry,
        protected readonly ComponentContextResolver $componentContextResolver,
    ) {}

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param  StaticCall  $node
     * @return array{schemaClass: string, callerClass: string}|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (! $this->isConfigureCall($node)) {
            return null;
        }

        $callerReflection = $scope->getClassReflection();
        if ($callerReflection === null) {
            return null;
        }

        $schemaClass = $scope->resolveName($node->class);
        $callerClass = $callerReflection->getName();

        $this->registry->registerCaller($schemaClass, $callerClass);

        $modelClass = $this->componentContextResolver->resolveModelClassFromScope($scope);
        if ($modelClass !== null) {
            $this->registry->register($schemaClass, $modelClass);
        }

        return [
            'schemaClass' => $schemaClass,
            'callerClass' => $callerClass,
        ];
    }

    /**
     * @phpstan-assert-if-true Node\Identifier $node->name
     * @phpstan-assert-if-true Name $node->class
     */
    protected function isConfigureCall(StaticCall $node): bool
    {
        return $node->name instanceof Node\Identifier
            && $node->name->name === 'configure'
            && $node->class instanceof Name;
    }
}
