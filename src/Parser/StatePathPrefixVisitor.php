<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Parser;

use ImSuperlative\PhpstanFilament\Support\AstHelper;
use ImSuperlative\PhpstanFilament\Support\FilamentComponent as FC;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;

final class StatePathPrefixVisitor extends NodeVisitorAbstract
{
    /**
     * Namespace prefixes for Filament field components (Entry, Field, Column).
     */
    protected const array FIELD_COMPONENT_PREFIXES = [
        FC::INFOLIST_COMPONENTS_NS,
        FC::FORM_COMPONENTS_NS,
        FC::TABLE_COLUMNS_NS,
    ];

    /**
     * Layout component classes whose make() name is a label, not a state path.
     */
    protected const array LAYOUT_CLASSES = [
        FC::INFOLIST_SECTION,
        FC::INFOLIST_GROUP,
        FC::INFOLIST_SPLIT,
        FC::INFOLIST_TABS,
        FC::FORM_SECTION,
        FC::FORM_GROUP,
        FC::FORM_FIELDSET,
        FC::FORM_TABS,
        FC::FORM_WIZARD,
        FC::SCHEMA_SECTION,
        FC::SCHEMA_GROUP,
    ];

    /**
     * @param  array<Node>  $nodes
     * @return array<Node>|null
     */
    public function afterTraverse(array $nodes): ?array
    {
        $this->walkNodes($nodes, []);

        return null;
    }

    /**
     * Recursively walk nodes, maintaining a prefix stack for nested schema() calls.
     *
     * @param  array<Node|mixed>  $nodes
     * @param  list<string>  $prefixStack
     */
    protected function walkNodes(array $nodes, array $prefixStack): void
    {
        foreach ($nodes as $node) {
            if (! $node instanceof Node) {
                continue;
            }

            if ($this->isSchemaOrComponentsCall($node)) {
                /** @var MethodCall $node */
                $parentName = $this->extractParentMakeName($node);

                if ($parentName !== null) {
                    $newStack = [...$prefixStack, $parentName];
                    $prefix = implode('.', $newStack);

                    $this->tagChildNodes($node, $prefix);

                    // Recurse into the schema array with updated stack
                    $schemaArray = AstHelper::resolveSchemaArray($node);
                    if ($schemaArray !== null) {
                        $this->walkNodes($schemaArray->items, $newStack);
                    }

                    continue;
                }
            }

            // Recurse into child nodes
            foreach ($node->getSubNodeNames() as $subNodeName) {
                $subNode = $node->{$subNodeName};

                if ($subNode instanceof Node) {
                    $this->walkNodes([$subNode], $prefixStack);
                } elseif (is_array($subNode)) {
                    $this->walkNodes($subNode, $prefixStack);
                }
            }
        }
    }

    /** @phpstan-assert-if-true MethodCall $node */
    protected function isSchemaOrComponentsCall(Node $node): bool
    {
        return $node instanceof MethodCall
            && $node->name instanceof Identifier
            && in_array($node->name->toString(), ['schema', 'components'], true);
    }

    protected function extractParentMakeName(MethodCall $methodCall): ?string
    {
        $root = AstHelper::methodChainRoot($methodCall->var);

        if (! $root instanceof StaticCall || ! $root->class instanceof Name) {
            return null;
        }

        $className = $root->class->toString();

        if (! $this->isLikelyFieldComponent($className)) {
            return null;
        }

        return AstHelper::extractMakeName($root);
    }

    protected function isLikelyFieldComponent(string $className): bool
    {
        if (in_array($className, self::LAYOUT_CLASSES, true)) {
            return false;
        }

        return array_any(self::FIELD_COMPONENT_PREFIXES, fn ($prefix) => str_starts_with($className, $prefix));
    }

    protected function tagChildNodes(MethodCall $schemaCall, string $prefix): void
    {
        $array = AstHelper::resolveSchemaArray($schemaCall);
        if ($array === null) {
            return;
        }

        $isParentVirtual = $this->isParentVirtual($schemaCall);

        foreach ($array->items as $item) {
            $root = AstHelper::methodChainRoot($item->value);

            if ($root instanceof StaticCall) {
                $root->setAttribute('filament.statePrefix', $prefix);

                if ($isParentVirtual) {
                    $root->setAttribute('filament.virtual', true);
                }
            }
        }
    }

    protected function isParentVirtual(MethodCall $schemaCall): bool
    {
        $parentRoot = AstHelper::methodChainRoot($schemaCall->var);

        return $parentRoot->getAttribute('filament.virtual') === true;
    }
}
