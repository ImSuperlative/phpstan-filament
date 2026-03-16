<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner\Indexing;

use ImSuperlative\PhpstanFilament\Data\FileMetadata;
use ImSuperlative\PhpstanFilament\Resolvers\Concerns\FilamentFileDiscovery;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Support\FilamentComponent as FC;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Support\NamespaceHelper;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;

class ProjectIndexer
{
    use FilamentFileDiscovery;

    protected const array ROOT_BASE_CLASSES = [
        FC::RESOURCE,
        // FC::RELATION_MANAGER,
        FC::MANAGE_RELATED_RECORDS,
    ];

    /**
     * @param  list<string>  $filamentPaths
     * @param  list<string>  $analysedPaths
     * @param  list<string>  $analysedPathsFromConfig
     */
    public function __construct(
        protected FileParser $fileParser,
        protected array $filamentPaths,
        protected string $currentWorkingDirectory,
        protected array $analysedPaths,
        protected array $analysedPathsFromConfig,
    ) {}

    public function index(): ProjectScanResult
    {
        $index = $this->discoverAndIndex();
        $roots = $this->findResourceRoots($index);

        return new ProjectScanResult(index: $index, roots: $roots);
    }

    /**
     * @return array<string, FileMetadata>
     */
    protected function discoverAndIndex(): array
    {
        $index = [];

        foreach ($this->discoverPhpFiles() as $filePath) {
            $code = file_get_contents($filePath);
            if ($code === false) {
                continue;
            }

            if (! str_contains($code, 'use Filament\\')) {
                continue;
            }

            $metadata = $this->parseFileMetadata($code);
            if ($metadata !== null) {
                $index[$filePath] = $metadata;
            }
        }

        return $index;
    }

    protected function parseFileMetadata(string $code): ?FileMetadata
    {
        $finder = $this->fileParser->nodeFinder();
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
}
