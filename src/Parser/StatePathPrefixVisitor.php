<?php

/** @noinspection ClassConstantCanBeUsedInspection */

namespace ImSuperlative\FilamentPhpstan\Parser;

use ImSuperlative\FilamentPhpstan\Support\AstHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Parser\Parser;

final class StatePathPrefixVisitor
{
    /**
     * Namespace prefixes for Filament field components (Entry, Field, Column).
     */
    protected const array FIELD_COMPONENT_PREFIXES = [
        'Filament\\Infolists\\Components\\',
        'Filament\\Forms\\Components\\',
        'Filament\\Tables\\Columns\\',
    ];

    /**
     * Layout component classes whose make() name is a label, not a state path.
     */
    protected const array LAYOUT_CLASSES = [
        'Filament\\Infolists\\Components\\Section',
        'Filament\\Infolists\\Components\\Group',
        'Filament\\Infolists\\Components\\Split',
        'Filament\\Infolists\\Components\\Tabs',
        'Filament\\Forms\\Components\\Section',
        'Filament\\Forms\\Components\\Group',
        'Filament\\Forms\\Components\\Fieldset',
        'Filament\\Forms\\Components\\Tabs',
        'Filament\\Forms\\Components\\Wizard',
        'Filament\\Schemas\\Components\\Section',
        'Filament\\Schemas\\Components\\Group',
    ];

    /** @var array<string, array<int, string>> file → (line → prefix) */
    protected array $cache = [];

    public function __construct(
        protected readonly Parser $parser,
    ) {}

    public function lookupPrefix(string $file, int $startLine): ?string
    {
        if (! isset($this->cache[$file])) {
            $this->cache[$file] = $this->buildPrefixMap($file);
        }

        return $this->cache[$file][$startLine] ?? null;
    }

    /**
     * @return array<int, string>
     */
    protected function buildPrefixMap(string $file): array
    {
        $map = [];
        $nodes = $this->parser->parseFile($file);
        $this->walkNodes($nodes, [], $map);

        return $map;
    }

    /**
     * Recursively walk nodes, maintaining a prefix stack for nested schema() calls.
     *
     * @param  array<Node|mixed>  $nodes
     * @param  list<string>  $prefixStack
     * @param  array<int, string>  $map
     */
    protected function walkNodes(array $nodes, array $prefixStack, array &$map): void
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

                    $this->registerChildPrefixes($node, $prefix, $map);

                    // Recurse into the schema array with updated stack
                    $schemaArray = $this->resolveSchemaArray($node);
                    if ($schemaArray !== null) {
                        $this->walkNodes($schemaArray->items, $newStack, $map);
                    }

                    continue;
                }
            }

            // Recurse into child nodes
            foreach ($node->getSubNodeNames() as $subNodeName) {
                $subNode = $node->{$subNodeName};

                if ($subNode instanceof Node) {
                    $this->walkNodes([$subNode], $prefixStack, $map);
                } elseif (is_array($subNode)) {
                    $this->walkNodes($subNode, $prefixStack, $map);
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

    /** @param  array<int, string>  $map */
    protected function registerChildPrefixes(MethodCall $schemaCall, string $prefix, array &$map): void
    {
        $array = $this->resolveSchemaArray($schemaCall);
        if ($array === null) {
            return;
        }

        foreach ($array->items as $item) {
            $root = AstHelper::methodChainRoot($item->value);

            if ($root instanceof StaticCall && $root->getStartLine() > 0) {
                $map[$root->getStartLine()] = $prefix;
            }
        }
    }

    /**
     * Resolve the schema array from a ->schema() / ->components() call,
     * handling direct arrays, arrow functions, and closures.
     */
    protected function resolveSchemaArray(MethodCall $schemaCall): ?Array_
    {
        $arg = AstHelper::firstArgValue($schemaCall);

        if ($arg instanceof Array_) {
            return $arg;
        }

        // fn (): array => [...]
        if ($arg instanceof ArrowFunction && $arg->expr instanceof Array_) {
            return $arg->expr;
        }

        // function () { return [...]; }
        if ($arg instanceof Closure && count($arg->stmts) === 1) {
            $stmt = $arg->stmts[0];
            if ($stmt instanceof Return_ && $stmt->expr instanceof Array_) {
                return $stmt->expr;
            }
        }

        return null;
    }
}
