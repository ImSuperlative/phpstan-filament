<?php

/** @noinspection ClassConstantCanBeUsedInspection */

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Resolvers\Concerns;

use ImSuperlative\PhpstanFilament\Support\NamespaceHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;

trait CallerMapDebugging
{
    /**
     * Run the scan pipeline and return graph + annotations for debugging.
     *
     * @return array{roots: list<string>, graph: array<string, list<string>>, annotations: array<string, list<\ImSuperlative\PhpstanFilament\Data\FilamentPageAnnotation>>}
     */
    public function scanDump(): array
    {
        $filePaths = $this->discoverFilamentFiles();
        $index = $this->indexFileMetadata($filePaths);
        $classToFile = $this->mapClassNamesToFiles($index);
        $roots = $this->findResourceRoots($index);
        $graph = $this->buildClassDependencyGraph($roots, $index, $classToFile);
        $contextMap = $this->assignRootsToReachableClasses($roots, $index, $graph);
        $annotations = $this->buildPageAnnotations($contextMap);

        $rootClasses = array_map(
            fn (string $filePath) => $index[$filePath]->fullyQualifiedName,
            $roots,
        );

        return [
            'roots' => $rootClasses,
            'graph' => $graph,
            'annotations' => $annotations,
        ];
    }

    /**
     * Dump the caller map as a nested tree for debugging.
     *
     * @return list<string>
     */
    public function dumpCallerTree(): array
    {
        $callerMap = $this->buildCallerMap();
        $lines = [];

        ksort($callerMap);

        foreach ($callerMap as $target => $directCallers) {
            $lines[] = $target;

            sort($directCallers);

            foreach ($directCallers as $caller) {
                $lines = $this->dumpCallerBranch($caller, $callerMap, $lines, 1, [$target]);
            }

            $lines[] = '';
        }

        return $lines;
    }

    /**
     * Build caller map: target class => list of caller classes.
     *
     * @return array<string, list<string>>
     */
    protected function buildCallerMap(): array
    {
        $callers = [];
        $finder = $this->fileParser->nodeFinder();

        foreach ($this->discoverPhpFiles() as $filePath) {
            $code = file_get_contents($filePath);
            if ($code === false) {
                continue;
            }

            $hasConfigureCall = str_contains($code, '::configure(');
            $hasConfigureMethodDefinition = str_contains($code, 'function configure(Schema')
                || str_contains($code, 'function configure(Table');

            if (! $hasConfigureCall && ! $hasConfigureMethodDefinition) {
                continue;
            }

            $stmts = $this->fileParser->parse($code);

            $callers = $this->mergeCallerMaps(
                $callers,
                $this->processStatements($stmts, $finder, $hasConfigureCall, $hasConfigureMethodDefinition),
            );
        }

        return $callers;
    }

    /**
     * @param  array<Node>  $stmts
     * @return array<string, list<string>>
     */
    protected function processStatements(
        array $stmts,
        NodeFinder $finder,
        bool $hasConfigureCall,
        bool $hasConfigureMethodDefinition
    ): array {
        $useMap = NamespaceHelper::buildQualifiedImportMapFromAst($stmts, $finder);
        $namespace = NamespaceHelper::findNamespaceDeclaration($stmts, $finder);

        /** @var list<StaticCall> $calls */
        $calls = $finder->findInstanceOf($stmts, StaticCall::class);

        $callers = $hasConfigureCall
            ? $this->collectConfigureCallers($calls, $useMap, $namespace, $stmts, $finder)
            : [];

        if ($hasConfigureMethodDefinition) {
            $callers = $this->mergeCallerMaps(
                $callers,
                $this->collectCustomComponentCallers($calls, $useMap, $namespace, $stmts, $finder),
            );
        }

        return $callers;
    }

    /**
     * @param  list<StaticCall>  $calls
     * @param  array<string, string>  $useMap
     * @param  array<Node>  $stmts
     * @return array<string, list<string>>
     */
    protected function collectConfigureCallers(
        array $calls,
        array $useMap,
        ?string $namespace,
        array $stmts,
        NodeFinder $finder
    ): array {
        $callers = [];

        foreach ($calls as $call) {
            if (! $this->isNamedStaticCall($call, 'configure')) {
                continue;
            }

            $schemaClass = NamespaceHelper::toFullyQualified((string) $call->class, $useMap, $namespace);
            $callerClass = $this->resolveCallerClassFullyQualifiedName($call, $stmts, $finder, $namespace);

            if ($callerClass !== null) {
                $callers = $this->mergeCallerMaps($callers, [$schemaClass => [$callerClass]]);
            }
        }

        return $callers;
    }

    /**
     * @param  list<StaticCall>  $calls
     * @param  array<string, string>  $useMap
     * @param  array<Node>  $stmts
     * @return array<string, list<string>>
     */
    protected function collectCustomComponentCallers(
        array $calls,
        array $useMap,
        ?string $namespace,
        array $stmts,
        NodeFinder $finder
    ): array {
        $callers = [];

        foreach ($calls as $call) {
            if (! $this->isNamedStaticCall($call, 'make')) {
                continue;
            }

            $componentClass = NamespaceHelper::toFullyQualified((string) $call->class, $useMap, $namespace);

            if ($this->isFilamentClass($componentClass)) {
                continue;
            }

            $callerClass = $this->resolveCallerClassFullyQualifiedName($call, $stmts, $finder, $namespace);

            if ($callerClass !== null && $callerClass !== $componentClass) {
                $callers = $this->mergeCallerMaps($callers, [$componentClass => [$callerClass]]);
            }
        }

        return $callers;
    }

    /**
     * @phpstan-assert-if-true Identifier $call->name
     * @phpstan-assert-if-true Name $call->class
     */
    protected function isNamedStaticCall(StaticCall $call, string $methodName): bool
    {
        return $call->name instanceof Identifier
            && $call->name->name === $methodName
            && $call->class instanceof Name;
    }

    /**
     * @param  array<string, list<string>>  $base
     * @param  array<string, list<string>>  $additions
     * @return array<string, list<string>>
     */
    protected function mergeCallerMaps(array $base, array $additions): array
    {
        foreach ($additions as $target => $callerList) {
            foreach ($callerList as $caller) {
                if (! in_array($caller, $base[$target] ?? [], true)) {
                    $base[$target][] = $caller;
                }
            }
        }

        return $base;
    }

    /**
     * @param  array<Node>  $stmts
     */
    protected function resolveCallerClassFullyQualifiedName(
        StaticCall $call,
        array $stmts,
        NodeFinder $finder,
        ?string $namespace
    ): ?string {
        $callerClass = $this->findClassNameContainingCall($call, $stmts, $finder);

        return $callerClass !== null && NamespaceHelper::isRelativeNamespace($namespace)
            ? NamespaceHelper::prependNamespace($namespace, $callerClass)
            : $callerClass;
    }

    /**
     * @param  array<Node>  $stmts
     */
    protected function findClassNameContainingCall(StaticCall $call, array $stmts, NodeFinder $finder): ?string
    {
        /** @var list<Class_> $classes */
        $classes = $finder->findInstanceOf($stmts, Class_::class);

        foreach ($classes as $class) {
            if ($class->name === null) {
                continue;
            }

            if ($this->isCallWithinClass($call, $class)) {
                return (string) $class->name;
            }
        }

        return null;
    }

    protected function isCallWithinClass(Node $node, Class_ $class): bool
    {
        return $node->getStartLine() >= $class->getStartLine()
            && $node->getEndLine() <= $class->getEndLine();
    }

    /**
     * @param  array<string, list<string>>  $callerMap
     * @param  list<string>  $lines
     * @param  list<string>  $visited
     * @return list<string>
     */
    protected function dumpCallerBranch(
        string $caller,
        array $callerMap,
        array $lines,
        int $depth,
        array $visited
    ): array {
        $indent = str_repeat('  ', $depth);
        $lines[] = "{$indent}← $caller";

        if (in_array($caller, $visited, true)) {
            return $lines;
        }

        $transitiveCallers = $callerMap[$caller] ?? [];
        sort($transitiveCallers);

        foreach ($transitiveCallers as $transitive) {
            $lines = $this->dumpCallerBranch($transitive, $callerMap, $lines, $depth + 1, [...$visited, $caller]);
        }

        return $lines;
    }
}
