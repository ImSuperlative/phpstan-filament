<?php

/** @noinspection ClassConstantCanBeUsedInspection */

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner;

use ImSuperlative\PhpstanFilament\Data\FileMetadata;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourcePages;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceRelations;
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

final class FilamentProjectScanner
{
    use FilamentFileDiscovery;

    protected const array ROOT_BASE_CLASSES = [
        'Filament\\Resources\\Resource',
        'Filament\\Resources\\RelationManagers\\RelationManager',
        'Filament\\Resources\\Pages\\ManageRelatedRecords',
    ];

    /**
     * @param  list<GraphTransformer>  $graphTransformers
     * @param  list<EnrichmentTransformer>  $enrichmentTransformers
     * @param  list<string>  $filamentPaths
     * @param  list<string>  $analysedPaths
     * @param  list<string>  $analysedPathsFromConfig
     */
    public function __construct(
        protected FileParser $fileParser,
        protected array $graphTransformers,
        protected array $enrichmentTransformers,
        protected array $filamentPaths,
        protected string $currentWorkingDirectory,
        protected array $analysedPaths,
        protected array $analysedPathsFromConfig,
    ) {}

    public function scan(): ProjectScanResult
    {
        // Stage 1: Find roots
        $filePaths = $this->discoverFilamentFiles();
        $index = $this->indexFileMetadata($filePaths);
        $roots = $this->findResourceRoots($index);

        $result = new ProjectScanResult(index: $index, roots: $roots);

        // Stage 2: Graph transformers (enrich root declarations)
        foreach ($this->graphTransformers as $transformer) {
            $result = $transformer->transform($result);
        }

        // Stage 3: Component discovery (graph walk from roots + pages + relations)
        $result = $this->discoverComponents($result);

        // Stage 4: Enrichment transformers (full picture available)
        foreach ($this->enrichmentTransformers as $transformer) {
            $result = $transformer->transform($result);
        }

        return $result;
    }

    protected function discoverComponents(ProjectScanResult $result): ProjectScanResult
    {
        $classToFile = $this->mapClassNamesToFiles($result->index);

        $startingPoints = $result->roots;

        foreach ($result->get(ResourcePages::class)?->all() ?? [] as $pages) {
            foreach ($pages as $pageFqcn) {
                $filePath = $classToFile[$pageFqcn] ?? null;
                if ($filePath !== null) {
                    $startingPoints[] = $filePath;
                }
            }
        }

        foreach ($result->get(ResourceRelations::class)?->all() ?? [] as $relations) {
            foreach ($relations as $relationFqcn) {
                $filePath = $classToFile[$relationFqcn] ?? null;
                if ($filePath !== null) {
                    $startingPoints[] = $filePath;
                }
            }
        }

        $graph = $this->buildClassDependencyGraph($startingPoints, $result->index, $classToFile);
        $componentToResources = $this->assignRootsToReachableClasses(
            $result->roots, $result->index, $graph
        );

        return $result->set(new ComponentToResources($componentToResources));
    }

    /** @return list<string> */
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
     * @param  array<string, FileMetadata>  $index
     * @return list<string>
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

    /**
     * @param  list<string>  $rootFilePaths
     * @param  array<string, FileMetadata>  $index
     * @param  array<string, string>  $fullyQualifiedNameLookup
     * @return array<string, list<string>>
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

            if (! isset($index[$filePath])) {
                continue;
            }

            $record = $index[$filePath];
            $sourceClass = $record->fullyQualifiedName;
            $targets = $this->collectDependenciesForClass($filePath, $record, $fullyQualifiedNameLookup);

            if ($targets !== []) {
                $graph[$sourceClass] = $targets;
            }

            foreach ($targets as $targetClass) {
                $targetFile = $fullyQualifiedNameLookup[$targetClass] ?? null;
                if ($targetFile !== null && ! isset($visited[$targetFile])) {
                    $queue[] = $targetFile;
                }
            }
        }

        return $graph;
    }

    /**
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

    /** @return list<string> */
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
     * @param  list<string>  $rootFilePaths
     * @param  array<string, FileMetadata>  $index
     * @param  array<string, list<string>>  $graph
     * @return array<string, list<string>>
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
                [...$visited, $targetClass],
            );
        }

        return $contextMap;
    }
}
