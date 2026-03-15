<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner\Indexing;

use ImSuperlative\PhpstanFilament\Data\FileMetadata;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Data\Scanner\DependencyGraph;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\GraphTransformer;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Support\NamespaceHelper;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class ComponentDiscovery
{
    /**
     * @param  list<GraphTransformer>  $graphTransformers
     */
    public function __construct(
        protected FileParser $fileParser,
        protected array $graphTransformers,
    ) {}

    public function discover(ProjectScanResult $result): ProjectScanResult
    {
        $classToFile = $this->mapClassNamesToFiles($result->index);

        $startingPoints = $this->collectStartingPoints($result, $classToFile);
        $graph = $this->buildClassDependencyGraph($startingPoints, $result->index, $classToFile);
        $componentToResources = $this->assignRootsToReachableClasses($result->roots, $result->index, $graph);
        $componentToResources = $this->registerRootsAsComponents($result, $componentToResources);
        $componentToResources = $this->applyDirectMappings($result, $componentToResources);

        $result->set(new DependencyGraph($graph));

        return $result->set(new ComponentToResources($componentToResources));
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
     * Gather file paths from transformer-declared components (pages, relation managers).
     *
     * @param  array<string, string>  $classToFile
     * @return list<string>
     */
    protected function collectStartingPoints(ProjectScanResult $result, array $classToFile): array
    {
        $startingPoints = $result->roots;

        foreach ($this->graphTransformers as $transformer) {
            foreach ($transformer->componentMappings($result) as $components) {
                foreach ($components as $fqcn) {
                    $filePath = $classToFile[$fqcn] ?? null;
                    if ($filePath !== null) {
                        $startingPoints[] = $filePath;
                    }
                }
            }
        }

        return $startingPoints;
    }

    /**
     * Roots not yet discovered as components own themselves (i.e. Resource classes).
     *
     * @param  array<string, list<string>>  $componentToResources
     * @return array<string, list<string>>
     */
    protected function registerRootsAsComponents(ProjectScanResult $result, array $componentToResources): array
    {
        foreach ($result->roots as $rootFilePath) {
            $rootClass = $result->index[$rootFilePath]->fullyQualifiedName;
            if (! isset($componentToResources[$rootClass])) {
                $componentToResources[$rootClass][] = $rootClass;
            }
        }

        return $componentToResources;
    }

    /**
     * Apply direct component mappings from all graph transformers.
     *
     * @param  array<string, list<string>>  $componentToResources
     * @return array<string, list<string>>
     */
    protected function applyDirectMappings(ProjectScanResult $result, array $componentToResources): array
    {
        foreach ($this->graphTransformers as $transformer) {
            foreach ($transformer->componentMappings($result) as $resourceClass => $components) {
                foreach ($components as $fqcn) {
                    $componentToResources[$fqcn][] = $resourceClass;
                }
            }
        }

        return $componentToResources;
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
        array $fullyQualifiedNameLookup,
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
        array $fullyQualifiedNameLookup,
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
                $record->namespace,
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
        array $visited,
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

    protected function isFilamentClass(string $className): bool
    {
        return str_starts_with($className, 'Filament\\');
    }
}
