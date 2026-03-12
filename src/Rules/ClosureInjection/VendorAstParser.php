<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Rules\ClosureInjection;

use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Support\NamespaceHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeFinder;

final class VendorAstParser
{
    public function __construct(
        protected readonly FileParser $fileParser,
    ) {}

    /**
     * Parse resolveDefaultClosureDependencyForEvaluationByName.
     *
     * @return array<string, string> paramName => getterMethodName
     */
    public function parseByNameMethod(string $filePath): array
    {
        [$stmts, $finder] = $this->parse($filePath);

        $method = $this->findClassMethod($stmts, $finder, 'resolveDefaultClosureDependencyForEvaluationByName');
        if ($method === null) {
            return [];
        }

        $result = [];

        foreach ($this->collectMatchArms($method, $finder) as [$cond, $getter]) {
            if ($cond instanceof String_) {
                $result[$cond->value] = $getter;
            }
        }

        return $result;
    }

    /**
     * Parse resolveDefaultClosureDependencyForEvaluationByType.
     *
     * @return array<string, string> typeFQCN => getterMethodName
     */
    public function parseByTypeMethod(string $filePath): array
    {
        [$stmts, $finder] = $this->parse($filePath);

        $useMap = NamespaceHelper::buildQualifiedImportMapFromAst($stmts, $finder);

        $method = $this->findClassMethod($stmts, $finder, 'resolveDefaultClosureDependencyForEvaluationByType');
        if ($method === null) {
            return [];
        }

        $result = [];

        foreach ($this->collectMatchArms($method, $finder) as [$cond, $getter]) {
            if (! $cond instanceof Node\Expr\ClassConstFetch
                || ! $cond->name instanceof Identifier || $cond->name->name !== 'class'
                || ! $cond->class instanceof Name) {
                continue;
            }

            $shortName = (string) $cond->class;
            $result[$useMap[$shortName] ?? $shortName] = $getter;
        }

        return $result;
    }

    /**
     * Parse the evaluationIdentifier property default value.
     * Returns null if the property is declared without a default string value.
     */
    public function parseEvaluationIdentifier(string $filePath): ?string
    {
        [$stmts, $finder] = $this->parse($filePath);

        /** @var Property|null $property */
        $property = $finder->findFirst($stmts, fn (Node $n): bool => $n instanceof Property &&
            count($n->props) > 0 &&
            (string) $n->props[0]->name === 'evaluationIdentifier'
        );

        if ($property === null) {
            return null;
        }

        $default = $property->props[0]->default;
        if (! ($default instanceof String_)) {
            return null;
        }

        return $default->value;
    }

    /**
     * Parse a file and return [stmts, NodeFinder].
     *
     * @return array{array<Node\Stmt>, NodeFinder}
     */
    protected function parse(string $filePath): array
    {
        return [
            $this->fileParser->parseFile($filePath) ?? [],
            $this->fileParser->nodeFinder(),
        ];
    }

    /**
     * Find a ClassMethod by name in the AST.
     *
     * @param  array<Node>  $stmts
     */
    protected function findClassMethod(array $stmts, NodeFinder $finder, string $methodName): ?ClassMethod
    {
        /** @var ClassMethod|null */
        return $finder->findFirst($stmts, fn (Node $n): bool => $n instanceof ClassMethod
            && (string) $n->name === $methodName
        );
    }

    /**
     * Collect all [condition, getter] pairs from match arms in a method.
     *
     * @return list<array{Node, string}>
     */
    protected function collectMatchArms(ClassMethod $method, NodeFinder $finder): array
    {
        /** @var list<Match_> $matches */
        $matches = $finder->findInstanceOf($method, Match_::class);

        return array_merge(
            ...array_map(fn (Match_ $match) => $this->flattenMatchConditions($match), $matches),
        );
    }

    /**
     * Flatten a match expression into [condition, getter] pairs,
     * skipping default arms and arms without a valid getter.
     *
     * @return list<array{Node, string}>
     */
    protected function flattenMatchConditions(Match_ $match): array
    {
        $pairs = [];

        foreach ($match->arms as $arm) {
            if ($arm->conds === null) {
                continue;
            }

            $getter = $this->extractGetterFromArrayBody($arm);
            if ($getter === null) {
                continue;
            }

            foreach ($arm->conds as $cond) {
                $pairs[] = [$cond, $getter];
            }
        }

        return $pairs;
    }

    /**
     * Extract the getter method name from a match arm body of the form [$this->methodName()]
     * or a chained call like [$this->getContainer()->getOperation()].
     *
     * Returns the innermost $this->method() name for simple calls.
     * Returns an empty string as a sentinel for chained/complex expressions that are valid
     * but whose return type cannot be statically resolved (will map to MixedType).
     * Returns null if the arm body does not match the expected array-wrapping pattern at all.
     */
    protected function extractGetterFromArrayBody(MatchArm $arm): ?string
    {
        $body = $arm->body;

        // Must be an array with exactly one item
        if (
            ! $body instanceof Node\Expr\Array_
            || count($body->items) !== 1
        ) {
            return null;
        }

        $item = $body->items[0]->value ?? null;
        if ($item === null) {
            return null;
        }

        // Simple pattern: [$this->method()]
        if (
            $item instanceof MethodCall
            && $item->var instanceof Node\Expr\Variable
            && $item->var->name === 'this'
            && $item->name instanceof Identifier
        ) {
            return $item->name->name;
        }

        // Chained pattern: [$this->something()->method()] — valid but return type is unknown.
        // Walk up through chained MethodCalls to see if the root is $this.
        if ($item instanceof MethodCall && $this->isRootedAtThis($item)) {
            // Return empty string as sentinel: "valid arm, but return type is mixed".
            return '';
        }

        return null;
    }

    /**
     * Recursively check whether a MethodCall chain is ultimately rooted at $this.
     */
    protected function isRootedAtThis(MethodCall $call): bool
    {
        /** @noinspection PhpParamsInspection */
        return match (true) {
            $call->var instanceof Node\Expr\Variable && $call->var->name === 'this' => true,
            $call->var instanceof MethodCall => $this->isRootedAtThis($call->var),
            default => false,
        };
    }
}
