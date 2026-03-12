<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner\Transformers;

use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use ImSuperlative\PhpstanFilament\Data\FileMetadata;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceRelations;
use ImSuperlative\PhpstanFilament\Scanner\GraphTransformer;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Support\NamespaceHelper;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;

final class RelationsTransformer implements GraphTransformer
{
    public function __construct(
        protected FileParser $fileParser,
    ) {}

    public function transform(ProjectScanResult $result): ProjectScanResult
    {
        $relations = [];

        foreach ($result->roots as $filePath) {
            if (! isset($result->index[$filePath])) {
                continue;
            }

            $record = $result->index[$filePath];
            $parsed = $this->parseGetRelations($filePath, $record);

            if ($parsed !== []) {
                $relations[$record->fullyQualifiedName] = $parsed;
            }
        }

        return $result->set(new ResourceRelations($relations));
    }

    /** @return list<string> relation manager FQCNs */
    protected function parseGetRelations(string $filePath, FileMetadata $record): array
    {
        $stmts = $this->fileParser->parseFile($filePath);

        if ($stmts === null) {
            return [];
        }

        $finder = $this->fileParser->nodeFinder();

        /** @var ClassMethod|null $method */
        $method = $finder->findFirst(
            $stmts,
            fn ($node) => $node instanceof ClassMethod && $node->name->name === 'getRelations'
        );

        if ($method === null || $method->stmts === null) {
            return [];
        }

        /** @var Return_|null $returnNode */
        $returnNode = $finder->findFirst($method->stmts, fn ($node) => $node instanceof Return_);

        if (! $returnNode instanceof Return_ || ! $returnNode->expr instanceof Array_) {
            return [];
        }

        $managers = [];

        foreach ($returnNode->expr->items as $item) {
            // @phpstan-ignore instanceof.alwaysTrue (items may be null in some PhpParser versions)
            if (! $item instanceof ArrayItem) {
                continue;
            }

            array_push($managers, ...$this->resolveRelationManagers($item->value, $record));
        }

        return array_values(array_unique($managers));
    }

    /** @return list<string> */
    protected function resolveRelationManagers(Expr $expr, FileMetadata $record): array
    {
        if ($this->isClassReference($expr)) {
            /** @var ClassConstFetch&object{class: Name} $expr */
            return [NamespaceHelper::toFullyQualified((string) $expr->class, $record->useMap, $record->namespace)];
        }

        if ($this->isStaticMakeCall($expr)) {
            /** @var StaticCall $expr */
            return $this->resolveFromStaticMake($expr, $record);
        }

        return [];
    }

    /** @return list<string> */
    protected function resolveFromStaticMake(StaticCall $expr, FileMetadata $record): array
    {
        if (! $expr->class instanceof Name) {
            return [];
        }

        $className = NamespaceHelper::toFullyQualified((string) $expr->class, $record->useMap, $record->namespace);

        // RelationGroup::make('label', [Manager1::class, Manager2::class])
        if ($className === RelationGroup::class && isset($expr->args[1])) {
            $arrayArg = $expr->args[1]->value ?? null;

            if (! $arrayArg instanceof Array_) {
                return [];
            }

            $managers = [];
            foreach ($arrayArg->items as $nestedItem) {
                // @phpstan-ignore instanceof.alwaysTrue
                if ($nestedItem instanceof ArrayItem) {
                    array_push($managers, ...$this->resolveRelationManagers($nestedItem->value, $record));
                }
            }

            return $managers;
        }

        // RelationManagerConfiguration::make(Manager::class, ...)
        if ($className === RelationManagerConfiguration::class && isset($expr->args[0])) {
            $firstArg = $expr->args[0]->value ?? null;

            return $firstArg !== null ? $this->resolveRelationManagers($firstArg, $record) : [];
        }

        return [];
    }

    /** @phpstan-assert-if-true ClassConstFetch $expr */
    protected function isClassReference(Expr $expr): bool
    {
        return $expr instanceof ClassConstFetch
            && $expr->class instanceof Name
            && $expr->name instanceof Identifier
            && $expr->name->name === 'class';
    }

    /** @phpstan-assert-if-true StaticCall $expr */
    protected function isStaticMakeCall(Expr $expr): bool
    {
        return $expr instanceof StaticCall
            && $expr->class instanceof Name
            && $expr->name instanceof Identifier
            && $expr->name->name === 'make';
    }
}
