<?php

/** @noinspection ClassConstantCanBeUsedInspection */

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Resolvers;

use ImSuperlative\PhpstanFilament\Data\FilamentPageAnnotation;
use ImSuperlative\PhpstanFilament\Data\FileMetadata;
use ImSuperlative\PhpstanFilament\Resolvers\Concerns\CallerMapDebugging;
use ImSuperlative\PhpstanFilament\Resolvers\Concerns\FilamentFileDiscovery;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Support\NamespaceHelper;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeFinder;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

final class VirtualAnnotationProvider
{
    use CallerMapDebugging;
    use FilamentFileDiscovery;

    protected const array ROOT_BASE_CLASSES = [
        'Filament\\Resources\\Resource',
        'Filament\\Resources\\RelationManagers\\RelationManager',
        'Filament\\Resources\\Pages\\ManageRelatedRecords',
    ];

    /** @var array<string, list<FilamentPageAnnotation>>|null className => annotations */
    public ?array $annotations = null;

    /**
     * @param  list<string>  $analysedPaths
     * @param  list<string>  $analysedPathsFromConfig
     * @param  list<string>  $filamentPaths
     */
    public function __construct(
        protected ResourceModelResolver $resourceModelResolver,
        protected FileParser $fileParser,
        protected bool $enabled,
        protected bool $warnOnVirtual,
        protected array $filamentPaths,
        protected string $currentWorkingDirectory,
        protected array $analysedPaths,
        protected array $analysedPathsFromConfig,
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
        $filePaths = $this->discoverFilamentFiles();
        $index = $this->indexFileMetadata($filePaths);
        $classToFile = $this->mapClassNamesToFiles($index);
        $roots = $this->findResourceRoots($index);
        $graph = $this->buildClassDependencyGraph($roots, $index, $classToFile);
        $contextMap = $this->assignRootsToReachableClasses($roots, $index, $graph);

        return $this->buildPageAnnotations($contextMap);
    }

    /**
     * Convert caller map to FilamentPageAnnotation objects.
     *
     * @param  array<string, list<string>>  $callerMap
     * @return array<string, list<FilamentPageAnnotation>>
     */
    protected function buildPageAnnotations(array $callerMap): array
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
     * Gate: discover PHP files that import from Filament.
     *
     * @return list<string> file paths
     */
    protected function discoverFilamentFiles(): array
    {
        $filamentFiles = [];

        foreach ($this->discoverPhpFiles() as $filePath) {
            $code = file_get_contents($filePath);
            if ($code === false) {
                continue;
            }

            if (! str_contains($code, 'use Filament\\')) {
                continue;
            }

            $filamentFiles[] = $filePath;
        }

        return $filamentFiles;
    }

    /**
     * Parse gated files and extract metadata for root identification and walking.
     *
     * @param  list<string>  $filePaths
     * @return array<string, FileMetadata>
     */
    protected function indexFileMetadata(array $filePaths): array
    {
        $index = [];
        $finder = $this->fileParser->nodeFinder();

        foreach ($filePaths as $filePath) {
            $metadata = $this->parseFileMetadata($filePath, $finder);
            if ($metadata !== null) {
                $index[$filePath] = $metadata;
            }
        }

        return $index;
    }

    protected function parseFileMetadata(string $filePath, NodeFinder $finder): ?FileMetadata
    {
        $code = file_get_contents($filePath);
        if ($code === false) {
            return null;
        }

        $stmts = $this->fileParser->parse($code);
        $namespace = NamespaceHelper::findNamespaceDeclaration($stmts, $finder);
        $useMap = NamespaceHelper::buildQualifiedImportMapFromAst($stmts, $finder);

        $class = $finder->findFirstInstanceOf($stmts, Class_::class);
        $trait = $class === null
            ? $finder->findFirstInstanceOf($stmts, Trait_::class)
            : null;

        $node = $class ?? $trait;
        if ($node === null || $node->name === null) {
            return null;
        }

        $fullyQualifiedName = NamespaceHelper::isRelativeNamespace($namespace)
            ? NamespaceHelper::prependNamespace($namespace, (string) $node->name)
            : (string) $node->name;

        $extends = ($class?->extends !== null)
            ? NamespaceHelper::toFullyQualified((string) $class->extends, $useMap, $namespace)
            : null;

        $traits = [];
        /** @var list<TraitUse> $traitUses */
        $traitUses = $finder->findInstanceOf($node->stmts, TraitUse::class);
        foreach ($traitUses as $traitUse) {
            foreach ($traitUse->traits as $traitName) {
                $traits[] = NamespaceHelper::toFullyQualified((string) $traitName, $useMap, $namespace);
            }
        }

        return new FileMetadata(
            fullyQualifiedName: $fullyQualifiedName,
            extends: $extends,
            traits: $traits,
            useMap: $useMap,
            namespace: $namespace,
            isTrait: $trait !== null,
        );
    }

    /**
     * Find root files — classes that extend known Filament base classes.
     *
     * @param  array<string, FileMetadata>  $index
     * @return list<string> file paths of root classes
     */
    protected function findResourceRoots(array $index): array
    {
        $roots = [];

        foreach ($index as $filePath => $record) {
            if (in_array($record->extends, self::ROOT_BASE_CLASSES, true)) {
                $roots[] = $filePath;
            }
        }

        return $roots;
    }

    /**
     * BFS from roots through the file index, collecting outgoing edges.
     *
     * @param  list<string>  $rootFilePaths
     * @param  array<string, FileMetadata>  $index
     * @param  array<string, string>  $fullyQualifiedNameLookup  fqcn => filePath
     * @return array<string, list<string>> source FQCN => list<target FQCN>
     */
    protected function buildClassDependencyGraph(
        array $rootFilePaths,
        array $index,
        array $fullyQualifiedNameLookup
    ): array {
        $graph = [];
        $visited = [];
        $queue = $rootFilePaths;

        while ($queue !== []) {
            $filePath = array_shift($queue);

            if (isset($visited[$filePath])) {
                continue;
            }
            $visited[$filePath] = true;

            $record = $index[$filePath];
            $sourceClass = $record->fullyQualifiedName;
            $targets = $this->collectDependenciesForClass($filePath, $record, $fullyQualifiedNameLookup);

            if ($targets !== []) {
                $graph[$sourceClass] = $targets;
            }

            // Enqueue unvisited targets
            foreach ($targets as $targetClass) {
                $targetFile = $fullyQualifiedNameLookup[$targetClass];
                if (! isset($visited[$targetFile])) {
                    $queue[] = $targetFile;
                }
            }
        }

        return $graph;
    }

    /**
     * Collect all dependency edges for a single class: static calls, extends, trait uses.
     *
     * @param  array<string, string>  $fullyQualifiedNameLookup
     * @return list<string>
     */
    protected function collectDependenciesForClass(
        string $filePath,
        FileMetadata $record,
        array $fullyQualifiedNameLookup
    ): array {
        $targets = array_filter(
            $this->findStaticCallTargets($filePath, $record),
            fn (string $class) => isset($fullyQualifiedNameLookup[$class]),
        );

        if ($record->extends !== null && isset($fullyQualifiedNameLookup[$record->extends])) {
            $targets[] = $record->extends;
        }

        foreach ($record->traits as $traitClass) {
            if (isset($fullyQualifiedNameLookup[$traitClass])) {
                $targets[] = $traitClass;
            }
        }

        return array_values(array_unique($targets));
    }

    /**
     * Find static call targets (::configure() and ::make()) in a file's AST.
     *
     * @return list<string>
     */
    protected function findStaticCallTargets(string $filePath, FileMetadata $record): array
    {
        $code = file_get_contents($filePath);
        if ($code === false) {
            return [];
        }

        $stmts = $this->fileParser->parse($code);
        $finder = $this->fileParser->nodeFinder();

        /** @var list<StaticCall> $calls */
        $calls = $finder->findInstanceOf($stmts, StaticCall::class);

        $targets = [];

        foreach ($calls as $call) {
            if (! $call->name instanceof Identifier || ! $call->class instanceof Name) {
                continue;
            }

            $methodName = $call->name->name;
            if ($methodName !== 'configure' && $methodName !== 'make') {
                continue;
            }

            $targetClass = NamespaceHelper::toFullyQualified(
                (string) $call->class,
                $record->useMap,
                $record->namespace
            );

            if (! $this->isFilamentClass($targetClass)) {
                $targets[] = $targetClass;
            }
        }

        return $targets;
    }

    /**
     * DFS from each root through the reference graph, tagging reachable nodes with root FQCNs.
     *
     * @param  list<string>  $rootFilePaths
     * @param  array<string, FileMetadata>  $index
     * @param  array<string, list<string>>  $graph  source FQCN => list<target FQCN>
     * @return array<string, list<string>> target FQCN => list<root FQCNs>
     */
    protected function assignRootsToReachableClasses(array $rootFilePaths, array $index, array $graph): array
    {
        $contextMap = [];

        foreach ($rootFilePaths as $rootFilePath) {
            $rootClass = $index[$rootFilePath]->fullyQualifiedName;
            $contextMap = $this->tagClassesReachableFromRoot($rootClass, $rootClass, $graph, $contextMap, []);
        }

        return $contextMap;
    }

    /**
     * @param  array<string, list<string>>  $graph
     * @param  array<string, list<string>>  $contextMap
     * @param  list<string>  $visited
     * @return array<string, list<string>>
     */
    protected function tagClassesReachableFromRoot(
        string $rootClass,
        string $currentFqcn,
        array $graph,
        array $contextMap,
        array $visited
    ): array {
        foreach ($graph[$currentFqcn] ?? [] as $targetClass) {
            if (in_array($targetClass, $visited, true)) {
                continue;
            }

            if (! in_array($rootClass, $contextMap[$targetClass] ?? [], true)) {
                $contextMap[$targetClass][] = $rootClass;
            }

            $contextMap = $this->tagClassesReachableFromRoot(
                $rootClass,
                $targetClass,
                $graph,
                $contextMap,
                [...$visited, $targetClass]
            );
        }

        return $contextMap;
    }

    /**
     * Build reverse lookup: FQCN => filePath.
     *
     * @param  array<string, FileMetadata>  $index
     * @return array<string, string>
     */
    protected function mapClassNamesToFiles(array $index): array
    {
        $lookup = [];

        foreach ($index as $filePath => $record) {
            $lookup[$record->fullyQualifiedName] = $filePath;
        }

        return $lookup;
    }
}
