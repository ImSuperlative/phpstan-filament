<?php

namespace ImSuperlative\FilamentPhpstan\Data;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

final readonly class FilamentPageAnnotation extends FilamentTagAnnotation
{
    /**
     * The page type node, stripping any generic wrapper.
     *
     * `@/filament-page EditPost<Post>` → IdentifierTypeNode("EditPost")
     * `@/filament-page EditPost` → IdentifierTypeNode("EditPost")
     */
    public function pageType(): TypeNode
    {
        return $this->type instanceof GenericTypeNode
            ? $this->type->type
            : $this->type;
    }

    /**
     * The model type from generic syntax, if present.
     *
     * `@/filament-page EditPost<Post>` → IdentifierTypeNode("Post")
     * `@/filament-page EditPost` → null
     */
    public function modelType(): ?TypeNode
    {
        return $this->type instanceof GenericTypeNode && $this->type->genericTypes !== []
            ? $this->type->genericTypes[0]
            : null;
    }

    /**
     * Individual page type nodes (handles unions).
     *
     * `@/filament-page EditPost|CreatePost` → [IdentifierTypeNode("EditPost"), IdentifierTypeNode("CreatePost")]
     * `@/filament-page EditPost<Post>` → [IdentifierTypeNode("EditPost")]
     * `@/filament-page EditPost` → [IdentifierTypeNode("EditPost")]
     *
     * @return array<TypeNode>
     */
    public function pageTypes(): array
    {
        $page = $this->pageType();

        return $page instanceof UnionTypeNode
            ? $page->types
            : [$page];
    }
}
