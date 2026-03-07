<?php

namespace ImSuperlative\FilamentPhpstan\Support;

use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeFinder;

final class NamespaceHelper
{
    public static function isFullyQualified(string $name): bool
    {
        return str_starts_with($name, '\\');
    }

    public static function stripLeadingBackslash(string $name): string
    {
        return ltrim($name, '\\');
    }

    /** @return list<string> */
    public static function splitSegments(string $name): array
    {
        return explode('\\', $name);
    }

    /** @param list<string> $segments */
    public static function joinSegments(array $segments): string
    {
        return implode('\\', $segments);
    }

    /** @phpstan-assert-if-true non-empty-string $namespace */
    public static function isRelativeNamespace(?string $namespace): bool
    {
        return $namespace !== null && $namespace !== '';
    }

    public static function prependNamespace(string $namespace, string $name): string
    {
        return $namespace.'\\'.$name;
    }

    /**
     * Try to resolve a short name via use imports.
     *
     * @param  array<string, string>  $useMap  alias => FQCN
     */
    public static function resolveFromUseMap(string $shortName, array $useMap): ?string
    {
        $segments = self::splitSegments($shortName);

        if (! isset($useMap[$segments[0]])) {
            return null;
        }

        $segments[0] = $useMap[$segments[0]];

        return self::joinSegments($segments);
    }

    /**
     * Resolve a short or aliased class name to its fully qualified form.
     *
     * @param  array<string, string>  $useMap  alias => FQCN
     */
    public static function toFullyQualified(string $shortName, array $useMap, ?string $namespace): string
    {
        if (self::isFullyQualified($shortName)) {
            return self::stripLeadingBackslash($shortName);
        }

        $resolved = self::resolveFromUseMap($shortName, $useMap);
        if ($resolved !== null) {
            return $resolved;
        }

        if (self::isRelativeNamespace($namespace)) {
            return self::prependNamespace($namespace, $shortName);
        }

        return $shortName;
    }

    /**
     * Find the namespace declaration from parsed statements.
     *
     * @param  array<Node>  $stmts
     */
    public static function findNamespaceDeclaration(array $stmts, NodeFinder $finder): ?string
    {
        /** @var Namespace_|null $ns */
        $ns = $finder->findFirstInstanceOf($stmts, Namespace_::class);

        return $ns?->name !== null ? (string) $ns->name : null;
    }

    /**
     * Build a map of short class name => qualified name from use statements in AST.
     *
     * @param  array<Node>  $stmts
     * @return array<string, string>
     */
    public static function buildQualifiedImportMapFromAst(array $stmts, NodeFinder $finder): array
    {
        $classImports = self::findClassImports($stmts, $finder);

        $map = [];
        foreach ($classImports as $useUse) {
            $alias = self::importAlias($useUse);
            $map[$alias] = (string) $useUse->name;
        }

        return $map;
    }

    /**
     * Find all class (non-function, non-const) use import clauses.
     *
     * @param  array<Node>  $stmts
     * @return list<UseItem>
     */
    protected static function findClassImports(array $stmts, NodeFinder $finder): array
    {
        /** @var list<Use_> $uses */
        $uses = $finder->findInstanceOf($stmts, Use_::class);

        $items = [];
        foreach ($uses as $use) {
            if ($use->type !== Use_::TYPE_NORMAL) {
                continue;
            }

            foreach ($use->uses as $useUse) {
                $items[] = $useUse;
            }
        }

        return $items;
    }

    protected static function importAlias(UseItem $useUse): string
    {
        return $useUse->alias !== null ? (string) $useUse->alias : $useUse->name->getLast();
    }
}
