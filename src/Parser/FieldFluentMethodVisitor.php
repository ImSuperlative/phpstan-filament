<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Parser;

use ImSuperlative\FilamentPhpstan\Support\AstHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

final class FieldFluentMethodVisitor extends NodeVisitorAbstract
{
    protected const array VIRTUAL_METHODS = ['state', 'getStateUsing', 'view'];

    protected const array COUNT_METHODS = ['counts', 'exists'];

    protected const array COLUMN_METHODS = ['avg', 'max', 'min', 'sum'];

    protected const array INFOLIST_ENTRY_PREFIXES = [
        'Filament\\Infolists\\Components\\',
    ];

    /**
     * Process the full AST after standard traversal.
     *
     * We use afterTraverse to walk the tree ourselves because we need to:
     * 1. Find method chains with override methods and tag their root ::make() node
     * 2. Find ->records() calls and tag all ::make() nodes in the enclosing method
     *
     * @param  array<Node>  $nodes
     * @return array<Node>|null
     */
    public function afterTraverse(array $nodes): ?array
    {
        $this->walkNodes($nodes, null);

        return null;
    }

    /**
     * @param  array<Node|mixed>  $nodes
     */
    protected function walkNodes(array $nodes, ?ClassMethod $enclosingMethod): void
    {
        foreach ($nodes as $node) {
            if (! $node instanceof Node) {
                continue;
            }

            if ($node instanceof ClassMethod) {
                $enclosingMethod = $node;
            }

            if ($node instanceof MethodCall) {
                $this->processMethodCall($node, $enclosingMethod);
            }

            foreach ($node->getSubNodeNames() as $subNodeName) {
                $subNode = $node->{$subNodeName};

                if ($subNode instanceof Node) {
                    $this->walkNodes([$subNode], $enclosingMethod);
                } elseif (is_array($subNode)) {
                    $this->walkNodes($subNode, $enclosingMethod);
                }
            }
        }
    }

    protected function processMethodCall(MethodCall $node, ?ClassMethod $enclosingMethod): void
    {
        if (! $node->name instanceof Identifier) {
            return;
        }

        $methodName = $node->name->toString();

        if ($this->isVirtualMethod($methodName)) {
            $this->tagRootMakeNode($node, 'filament.virtual', true);
            $this->propagateVirtualToSchemaChildren($node);

            return;
        }

        if ($methodName === 'placeholder') {
            $this->tagRootMakeNodeIfInfolistEntry($node);

            return;
        }

        if ($this->isAggregateMethod($methodName)) {
            $this->tagRootWithAggregateParts($node, $methodName);

            return;
        }

        if ($methodName === 'records' && $enclosingMethod !== null) {
            $this->tagAllMakeNodesInMethod($enclosingMethod);
        }
    }

    protected function isVirtualMethod(string $methodName): bool
    {
        return in_array($methodName, self::VIRTUAL_METHODS, true);
    }

    protected function isAggregateMethod(string $methodName): bool
    {
        return in_array($methodName, self::COUNT_METHODS, true)
            || in_array($methodName, self::COLUMN_METHODS, true);
    }

    protected function isInfolistEntryClass(string $className): bool
    {
        return array_any(self::INFOLIST_ENTRY_PREFIXES, fn (string $prefix) => str_starts_with($className, $prefix));
    }

    /**
     * Walk the chain from a virtual method toward ::make(), looking for
     * ->schema() or ->components() calls. Tag all child ::make() nodes
     * inside those arrays as virtual.
     *
     * Handles: RepeatableEntry::make('x')->schema([children])->state(...)
     * where ->state() wraps ->schema() in the AST.
     */
    protected function propagateVirtualToSchemaChildren(MethodCall $virtualCall): void
    {
        $current = $virtualCall->var;

        while ($current instanceof MethodCall) {
            if ($this->isSchemaOrComponentsMethod($current)) {
                $array = AstHelper::resolveSchemaArray($current);

                if ($array !== null) {
                    $this->setAttributeOnAllMakeNodes([$array], 'filament.virtual', true);
                }
            }

            $current = $current->var;
        }
    }

    protected function isSchemaOrComponentsMethod(MethodCall $call): bool
    {
        return $call->name instanceof Identifier
            && in_array($call->name->toString(), ['schema', 'components'], true);
    }

    protected function tagRootMakeNode(MethodCall $node, string $attribute, mixed $value): void
    {
        $root = AstHelper::methodChainRoot($node->var);

        if ($this->isStaticMakeCall($root)) {
            $root->setAttribute($attribute, $value);
        }
    }

    protected function tagRootMakeNodeIfInfolistEntry(MethodCall $node): void
    {
        $root = AstHelper::methodChainRoot($node->var);

        if (! $root->class instanceof Name || ! $this->isStaticMakeCall($root)) {
            return;
        }

        if ($this->isInfolistEntryClass($root->class->toString())) {
            $root->setAttribute('filament.virtual', true);
        }
    }

    protected function tagRootWithAggregateParts(MethodCall $node, string $methodName): void
    {
        $parts = $this->extractAggregateParts($node, $methodName);

        if ($parts !== null) {
            $this->tagRootMakeNode($node, 'filament.aggregate', $parts);
        }
    }

    /**
     * @return array{string, ?string}|null [relation, ?column]
     */
    protected function extractAggregateParts(MethodCall $node, string $methodName): ?array
    {
        $args = $node->getArgs();
        $relation = ($args[0] ?? null)?->value;

        if (! $relation instanceof String_) {
            return null;
        }

        if (in_array($methodName, self::COUNT_METHODS, true)) {
            return [$relation->value, null];
        }

        $column = ($args[1] ?? null)?->value;

        return [$relation->value, $column instanceof String_ ? $column->value : null];
    }

    protected function tagAllMakeNodesInMethod(ClassMethod $method): void
    {
        $this->setAttributeOnAllMakeNodes([$method], 'filament.scopeSkipped', true);
    }

    /**
     * @param  array<Node|mixed>  $nodes
     */
    protected function setAttributeOnAllMakeNodes(array $nodes, string $attribute, mixed $value): void
    {
        foreach ($nodes as $node) {
            if (! $node instanceof Node) {
                continue;
            }

            if ($node instanceof StaticCall && $this->isStaticMakeCall($node)) {
                $node->setAttribute($attribute, $value);
            }

            foreach ($node->getSubNodeNames() as $subNodeName) {
                $subNode = $node->{$subNodeName};

                if ($subNode instanceof Node) {
                    $this->setAttributeOnAllMakeNodes([$subNode], $attribute, $value);
                } elseif (is_array($subNode)) {
                    $this->setAttributeOnAllMakeNodes($subNode, $attribute, $value);
                }
            }
        }
    }

    protected function isStaticMakeCall(Node $node): bool
    {
        return $node instanceof StaticCall
            && $node->name instanceof Identifier
            && $node->name->toString() === 'make';
    }
}
