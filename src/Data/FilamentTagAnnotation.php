<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Data;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

readonly class FilamentTagAnnotation
{
    public function __construct(
        public TypeNode $type,
        public ?string $fieldName = null,
    ) {}

    public function typeAsString(): string
    {
        return (string) $this->type;
    }

    /** @phpstan-assert-if-true UnionTypeNode $this->type */
    public function isUnion(): bool
    {
        return $this->type instanceof UnionTypeNode;
    }

    /** @return array<TypeNode> */
    public function types(): array
    {
        return $this->type instanceof UnionTypeNode
            ? $this->type->types
            : [$this->type];
    }

    public function toStateAnnotation(): FilamentStateAnnotation
    {
        return new FilamentStateAnnotation(type: $this->type, fieldName: $this->fieldName);
    }

    public function toFieldAnnotation(): FilamentFieldAnnotation
    {
        return new FilamentFieldAnnotation(type: $this->type, fieldName: $this->fieldName);
    }

    public function toModelAnnotation(): FilamentModelAnnotation
    {
        return new FilamentModelAnnotation(type: $this->type, fieldName: $this->fieldName);
    }

    public function toPageAnnotation(): FilamentPageAnnotation
    {
        return new FilamentPageAnnotation(type: $this->type, fieldName: $this->fieldName);
    }
}
