<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Resolvers;

use ImSuperlative\PhpstanFilament\Data\FilamentPageAnnotation;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Support\NamespaceHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use Symfony\Component\Finder\Finder;

final class VirtualAnnotationProvider
{
    protected const array FILAMENT_PREFIXES = [
        'Filament\\',
    ];

    /** @var array<string, list<FilamentPageAnnotation>>|null className => annotations */
    protected ?array $annotations = null;

    /**
     * @param  list<string>  $analysedPaths
     * @param  list<string>  $analysedPathsFromConfig
     */
    public function __construct(
        protected readonly bool $enabled,
        protected readonly bool $warnOnVirtual,
        /** @var list<string> */
        protected readonly array $filamentPath,
        protected readonly string $currentWorkingDirectory,
        protected readonly array $analysedPaths,
        protected readonly array $analysedPathsFromConfig,
        protected readonly ResourceModelResolver $resourceModelResolver,
        protected readonly FileParser $fileParser,
    ) {}

    /**
     * Get virtual page annotations for a class.
     *
     * @return list<FilamentPageAnnotation>
     */
    public function getPageAnnotations(string $className): array
    {
        if (! $this->enabled && ! $this->warnOnVirtual) {
            return [];
        }

        $this->annotations ??= $this->scan();

        return $this->annotations[$className] ?? [];
    }

    /**
     * @return array<string, list<FilamentPageAnnotation>>
     */
    protected function scan(): array
    {
        $callerMap = $this->buildCallerMap();
        $callerMap = $this->flattenCallerMap($callerMap);

        return $this->buildAnnotations($callerMap);
    }

    /**
     * Transitively expand the caller map so that indirect callers become direct callers.
     *
     * @param  array<string, list<string>>  $callerMap
     * @return array<string, list<string>>
     */
    public function flattenCallerMap(array $callerMap): array
    {
        $flattened = [];

        foreach ($callerMap as $target => $callers) {
            $flattened[$target] = $this->collectTransitiveCallers($target, $callerMap, []);
        }

        return $flattened;
    }

    /**
     * @param  array<string, list<string>>  $callerMap
     * @param  list<string>  $visited
     * @return list<string>
     */
    protected function collectTransitiveCallers(string $target, array $callerMap, array $visited): array
    {
        $result = [];

        foreach ($callerMap[$target] ?? [] as $caller) {
            if (in_array($caller, $visited, true)) {
                continue;
            }

            $result[] = $caller;

            foreach ($this->collectTransitiveCallers($caller, $callerMap, [...$visited, $caller]) as $transitive) {
                if (! in_array($transitive, $result, true)) {
                    $result[] = $transitive;
                }
            }
        }

        return $result;
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
     * Convert caller map to FilamentPageAnnotation objects.
     *
     * @param  array<string, list<string>>  $callerMap
     * @return array<string, list<FilamentPageAnnotation>>
     */
    protected function buildAnnotations(array $callerMap): array
    {
        $annotations = [];

        foreach ($callerMap as $targetClass => $callerClasses) {
            foreach ($callerClasses as $caller) {
                $model = $this->resourceModelResolver->resolve($caller);
                $pageTypeNode = new IdentifierTypeNode($caller);

                $typeNode = $model !== null
                    ? new GenericTypeNode($pageTypeNode, [new IdentifierTypeNode($model)])
                    : $pageTypeNode;

                $annotations[$targetClass][] = new FilamentPageAnnotation(type: $typeNode);
            }
        }

        return $annotations;
    }

    /**
     * @param  array<Node>  $stmts
     * @return array<string, list<string>>
     */
    protected function processStatements(array $stmts, NodeFinder $finder, bool $hasConfigureCall, bool $hasConfigureMethodDefinition): array
    {
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
    protected function collectConfigureCallers(array $calls, array $useMap, ?string $namespace, array $stmts, NodeFinder $finder): array
    {
        $callers = [];

        foreach ($calls as $call) {
            if (! $this->isNamedStaticCall($call, 'configure')) {
                continue;
            }

            $schemaClass = NamespaceHelper::toFullyQualified((string) $call->class, $useMap, $namespace);
            $callerFqcn = $this->resolveEnclosingClass($call, $stmts, $finder, $namespace);

            if ($callerFqcn !== null) {
                $callers = $this->mergeCallerMaps($callers, [$schemaClass => [$callerFqcn]]);
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
    protected function collectCustomComponentCallers(array $calls, array $useMap, ?string $namespace, array $stmts, NodeFinder $finder): array
    {
        $callers = [];

        foreach ($calls as $call) {
            if (! $this->isNamedStaticCall($call, 'make')) {
                continue;
            }

            $componentClass = NamespaceHelper::toFullyQualified((string) $call->class, $useMap, $namespace);

            if ($this->isFilamentClass($componentClass)) {
                continue;
            }

            $callerFqcn = $this->resolveEnclosingClass($call, $stmts, $finder, $namespace);

            if ($callerFqcn !== null && $callerFqcn !== $componentClass) {
                $callers = $this->mergeCallerMaps($callers, [$componentClass => [$callerFqcn]]);
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

    protected function isFilamentClass(string $className): bool
    {
        return array_any(self::FILAMENT_PREFIXES, fn (string $prefix) => str_starts_with($className, $prefix));
    }

    /**
     * @param  array<Node>  $stmts
     */
    protected function resolveEnclosingClass(StaticCall $call, array $stmts, NodeFinder $finder, ?string $namespace): ?string
    {
        $callerClass = $this->findEnclosingClass($call, $stmts, $finder);

        return $callerClass !== null && NamespaceHelper::isRelativeNamespace($namespace)
            ? NamespaceHelper::prependNamespace($namespace, $callerClass)
            : $callerClass;
    }

    /**
     * @param  array<Node>  $stmts
     */
    protected function findEnclosingClass(StaticCall $call, array $stmts, NodeFinder $finder): ?string
    {
        /** @var list<Class_> $classes */
        $classes = $finder->findInstanceOf($stmts, Class_::class);

        foreach ($classes as $class) {
            if ($class->name === null) {
                continue;
            }

            if ($this->nodeIsInsideClass($call, $class)) {
                return (string) $class->name;
            }
        }

        return null;
    }

    protected function nodeIsInsideClass(Node $node, Class_ $class): bool
    {
        return $node->getStartLine() >= $class->getStartLine()
            && $node->getEndLine() <= $class->getEndLine();
    }

    /**
     * @return list<string>
     */
    protected function discoverPhpFiles(): array
    {
        $files = [];
        $allPaths = $this->resolveScanPaths();

        foreach ($allPaths as $path) {
            if (is_file($path)) {
                $files[] = $path;

                continue;
            }

            if (! is_dir($path)) {
                continue;
            }

            $sfFinder = new Finder;
            $sfFinder->files()->name('*.php')->in($path);

            foreach ($sfFinder as $file) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /** @return list<string> */
    protected function resolveScanPaths(): array
    {
        if ($this->filamentPath !== []) {
            return array_map(
                fn (string $path) => $this->currentWorkingDirectory.'/'.$path,
                $this->filamentPath,
            );
        }

        return array_values(array_unique(array_merge($this->analysedPaths, $this->analysedPathsFromConfig)));
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
                $this->dumpCallerBranch($caller, $callerMap, $lines, 1, [$target]);
            }

            $lines[] = '';
        }

        return $lines;
    }

    /**
     * @param  array<string, list<string>>  $callerMap
     * @param  list<string>  $lines
     * @param  list<string>  $visited
     */
    protected function dumpCallerBranch(string $caller, array $callerMap, array &$lines, int $depth, array $visited): void
    {
        $indent = str_repeat('  ', $depth);
        $lines[] = "{$indent}← $caller";

        if (in_array($caller, $visited, true)) {
            return;
        }

        $transitiveCallers = $callerMap[$caller] ?? [];
        sort($transitiveCallers);

        foreach ($transitiveCallers as $transitive) {
            $this->dumpCallerBranch($transitive, $callerMap, $lines, $depth + 1, [...$visited, $caller]);
        }
    }
}
