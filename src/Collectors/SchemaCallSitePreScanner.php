<?php

namespace ImSuperlative\FilamentPhpstan\Collectors;

use ImSuperlative\FilamentPhpstan\Support\NamespaceHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

/**
 * Pre-scans project files at first access to find:
 * 1. Foo::configure($schema) call sites (schema → caller mapping)
 * 2. Non-Filament ::make() calls in those same files (custom component → caller mapping)
 *
 * Returns caller mappings that the SchemaCallSiteRegistry can consume,
 * eliminating the collector ordering problem.
 */
final class SchemaCallSitePreScanner
{
    protected const array FILAMENT_PREFIXES = [
        'Filament\\',
    ];

    /** @var array<string, list<string>>|null schemaClass => callerClasses */
    protected ?array $callers = null;

    /**
     * @param  list<string>  $analysedPaths
     * @param  list<string>  $analysedPathsFromConfig
     */
    public function __construct(
        protected readonly bool $enabled,
        protected readonly array $analysedPaths,
        protected readonly array $analysedPathsFromConfig = [],
    ) {}

    /**
     * @return array<string, list<string>> schemaClass => callerClasses
     */
    public function getCallerMap(): array
    {
        if (! $this->enabled) {
            return [];
        }

        $this->callers ??= $this->scan();

        return $this->callers;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function scan(): array
    {
        $callers = [];
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $finder = new NodeFinder;

        foreach ($this->discoverPhpFiles() as $filePath) {
            $code = file_get_contents($filePath);
            if ($code === false) {
                continue;
            }

            // Pre-filter: resource pages call ::configure(), schema classes define configure(Schema)
            $hasConfigure = str_contains($code, '::configure(');
            $hasSchemaMethod = str_contains($code, 'function configure(Schema')
                || str_contains($code, 'function configure(Table');

            if (! $hasConfigure && ! $hasSchemaMethod) {
                continue;
            }

            $stmts = $parser->parse($code);
            if ($stmts === null) {
                continue;
            }

            $callers = $this->mergeCallerMaps(
                $callers,
                $this->processStatements($stmts, $finder, $hasConfigure, $hasSchemaMethod)
            );
        }

        return $callers;
    }

    /**
     * @param  array<Node>  $stmts
     * @return array<string, list<string>>
     */
    protected function processStatements(array $stmts, NodeFinder $finder, bool $hasConfigure, bool $hasSchemaMethod): array
    {
        $useMap = NamespaceHelper::buildQualifiedImportMapFromAst($stmts, $finder);
        $namespace = NamespaceHelper::findNamespaceDeclaration($stmts, $finder);

        /** @var list<StaticCall> $calls */
        $calls = $finder->findInstanceOf($stmts, StaticCall::class);

        $callers = $hasConfigure
            ? $this->collectConfigureCallers($calls, $useMap, $namespace, $stmts, $finder)
            : [];

        if ($hasSchemaMethod) {
            $callers = $this->mergeCallerMaps(
                $callers,
                $this->collectCustomComponentCallers($calls, $useMap, $namespace, $stmts, $finder)
            );
        }

        return $callers;
    }

    /**
     * Collect Foo::configure($schema) → caller class mappings.
     *
     * @param  list<StaticCall>  $calls
     * @param  array<string, string>  $useMap
     * @param  array<Node>  $stmts
     * @return array<string, list<string>>
     */
    protected function collectConfigureCallers(array $calls, array $useMap, ?string $namespace, array $stmts, NodeFinder $finder): array
    {
        $callers = [];

        foreach ($calls as $call) {
            if (! $call->name instanceof Identifier || $call->name->name !== 'configure' || ! $call->class instanceof Name) {
                continue;
            }

            $schemaClass = NamespaceHelper::toFullyQualified((string) $call->class, $useMap, $namespace);
            $callerFqcn = $this->resolveEnclosingClass($call, $stmts, $finder, $namespace);

            if ($callerFqcn !== null && ! in_array($callerFqcn, $callers[$schemaClass] ?? [], true)) {
                $callers[$schemaClass][] = $callerFqcn;
            }
        }

        return $callers;
    }

    /**
     * Collect non-Filament Foo::make() → caller class mappings (custom components).
     *
     * @param  list<StaticCall>  $calls
     * @param  array<string, string>  $useMap
     * @param  array<Node>  $stmts
     * @return array<string, list<string>>
     */
    protected function collectCustomComponentCallers(array $calls, array $useMap, ?string $namespace, array $stmts, NodeFinder $finder): array
    {
        $callers = [];

        foreach ($calls as $call) {
            if (! $call->name instanceof Identifier || $call->name->name !== 'make' || ! $call->class instanceof Name) {
                continue;
            }

            $componentClass = NamespaceHelper::toFullyQualified((string) $call->class, $useMap, $namespace);

            if ($this->isFilamentClass($componentClass)) {
                continue;
            }

            $callerFqcn = $this->resolveEnclosingClass($call, $stmts, $finder, $namespace);

            if ($callerFqcn === null || $callerFqcn === $componentClass) {
                continue;
            }

            if (! in_array($callerFqcn, $callers[$componentClass] ?? [], true)) {
                $callers[$componentClass][] = $callerFqcn;
            }
        }

        return $callers;
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

            if ($call->getStartLine() >= $class->getStartLine() && $call->getEndLine() <= $class->getEndLine()) {
                return (string) $class->name;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function discoverPhpFiles(): array
    {
        $files = [];
        $allPaths = array_unique(array_merge($this->analysedPaths, $this->analysedPathsFromConfig));

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
}
