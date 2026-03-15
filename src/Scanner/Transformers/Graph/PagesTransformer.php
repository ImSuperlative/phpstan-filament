<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner\Transformers\Graph;

use ImSuperlative\PhpstanFilament\Data\FileMetadata;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourcePages;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\GraphTransformer;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Support\NamespaceHelper;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;

final class PagesTransformer implements GraphTransformer
{
    public function __construct(
        protected FileParser $fileParser,
    ) {}

    public function transform(ProjectScanResult $result): ProjectScanResult
    {
        $pages = [];

        foreach ($result->roots as $filePath) {
            if (! isset($result->index[$filePath])) {
                continue;
            }

            $record = $result->index[$filePath];
            $parsed = $this->parseGetPages($filePath, $record);

            if ($parsed !== []) {
                $pages[$record->fullyQualifiedName] = $parsed;
            }
        }

        return $result->set(new ResourcePages($pages));
    }

    /** @return array<class-string, list<class-string>> */
    public function componentMappings(ProjectScanResult $result): array
    {
        $pages = $result->get(ResourcePages::class);

        return array_map(array_values(...), $pages->all());
    }

    /** @return array<string, string> slug => page FQCN */
    protected function parseGetPages(string $filePath, FileMetadata $record): array
    {
        $arrayItems = $this->findReturnedArrayItems($filePath, 'getPages');

        if ($arrayItems === null) {
            return [];
        }

        $pages = [];

        foreach ($arrayItems as $item) {
            if (! $item instanceof ArrayItem || ! $item->key instanceof String_) {
                continue;
            }

            $pageFqcn = $this->resolveRouteCall($item->value, $record);

            if ($pageFqcn !== null) {
                $pages[$item->key->value] = $pageFqcn;
            }
        }

        return $pages;
    }

    /** @return list<ArrayItem|Expr>|null */
    protected function findReturnedArrayItems(string $filePath, string $methodName): ?array
    {
        $stmts = $this->fileParser->parseFile($filePath);

        if ($stmts === null) {
            return null;
        }

        $finder = $this->fileParser->nodeFinder();

        /** @var ClassMethod|null $method */
        $method = $finder->findFirst(
            $stmts,
            fn ($node) => $node instanceof ClassMethod && $node->name->name === $methodName
        );

        if ($method === null || $method->stmts === null) {
            return null;
        }

        /** @var Return_|null $returnNode */
        $returnNode = $finder->findFirst($method->stmts, fn ($node) => $node instanceof Return_);

        if (! $returnNode instanceof Return_ || ! $returnNode->expr instanceof Array_) {
            return null;
        }

        return $returnNode->expr->items;
    }

    protected function resolveRouteCall(Expr $expr, FileMetadata $record): ?string
    {
        if (! $expr instanceof StaticCall
            || ! $expr->class instanceof Name
            || ! $expr->name instanceof Identifier
            || $expr->name->name !== 'route'
        ) {
            return null;
        }

        return NamespaceHelper::toFullyQualified(
            (string) $expr->class,
            $record->useMap,
            $record->namespace,
        );
    }
}
