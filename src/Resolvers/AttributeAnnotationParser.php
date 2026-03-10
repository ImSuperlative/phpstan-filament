<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Resolvers;

use ImSuperlative\PhpstanFilament\Attributes\FilamentField;
use ImSuperlative\PhpstanFilament\Attributes\FilamentModel;
use ImSuperlative\PhpstanFilament\Attributes\FilamentPage;
use ImSuperlative\PhpstanFilament\Attributes\FilamentState;
use ImSuperlative\PhpstanFilament\Data\FilamentFieldAnnotation;
use ImSuperlative\PhpstanFilament\Data\FilamentModelAnnotation;
use ImSuperlative\PhpstanFilament\Data\FilamentPageAnnotation;
use ImSuperlative\PhpstanFilament\Data\FilamentStateAnnotation;
use ImSuperlative\PhpstanFilament\Data\FilamentTagAnnotation;
use ImSuperlative\PhpstanFilament\Parser\TypeStringParser;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Parser\ParserException;
use PHPStan\Reflection\ClassReflection;
use ReflectionAttribute;

final class AttributeAnnotationParser implements AnnotationParser
{
    public function __construct(
        protected readonly TypeStringParser $typeStringParser,
    ) {}

    public function readModelAnnotation(ClassReflection $class, ?string $method = null): ?FilamentModelAnnotation
    {
        $attributes = $this->getAttributes($class, FilamentModel::class, $method);

        if ($attributes === []) {
            return null;
        }

        return $this->buildTagAnnotation($attributes[0]->type)->toModelAnnotation();
    }

    /** @return array<FilamentPageAnnotation> */
    public function readPageAnnotations(ClassReflection $class, ?string $method = null): array
    {
        return array_map(
            function (FilamentPage $attr) {
                $typeNode = $this->buildTypeNode($attr->type);

                if ($attr->model !== null) {
                    $typeNode = new GenericTypeNode(
                        $typeNode instanceof IdentifierTypeNode ? $typeNode : new IdentifierTypeNode($attr->type[0]),
                        [new IdentifierTypeNode($attr->model)],
                        [GenericTypeNode::VARIANCE_INVARIANT],
                    );
                }

                return new FilamentTagAnnotation($typeNode)->toPageAnnotation();
            },
            $this->getAttributes($class, FilamentPage::class, $method),
        );
    }

    /** @return array<FilamentStateAnnotation> */
    public function readStateAnnotations(ClassReflection $class, ?string $method = null): array
    {
        return array_map(
            fn (FilamentState $attr) => $this->buildTagAnnotation($attr->type, $attr->field)->toStateAnnotation(),
            $this->getAttributes($class, FilamentState::class, $method),
        );
    }

    /** @return array<FilamentFieldAnnotation> */
    public function readFieldAnnotations(ClassReflection $class, ?string $method = null): array
    {
        return array_map(
            fn (FilamentField $attr) => $this->buildTagAnnotation($attr->type, $attr->field)->toFieldAnnotation(),
            $this->getAttributes($class, FilamentField::class, $method),
        );
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $attributeClass
     * @return list<T>
     */
    protected function getAttributes(ClassReflection $class, string $attributeClass, ?string $method): array
    {
        $reflector = $method !== null
            ? $class->getNativeReflection()->getMethod($method)
            : $class->getNativeReflection();

        return array_map(
            fn (ReflectionAttribute $attr) => $attr->newInstance(),
            $reflector->getAttributes($attributeClass),
        );
    }

    /** @param list<string> $types */
    protected function buildTagAnnotation(array $types, ?string $fieldName = null): FilamentTagAnnotation
    {
        return new FilamentTagAnnotation(
            type: $this->buildTypeNode($types),
            fieldName: $fieldName,
        );
    }

    /** @param list<string> $types */
    protected function buildTypeNode(array $types): TypeNode
    {
        if (count($types) === 1 && str_contains($types[0], '|')) {
            return $this->parseTypeExpression($types[0]);
        }

        $nodes = array_map(
            fn (string $type) => new IdentifierTypeNode($type),
            $types,
        );

        return count($nodes) === 1 ? $nodes[0] : new UnionTypeNode($nodes);
    }

    protected function parseTypeExpression(string $expression): TypeNode
    {
        try {
            return $this->typeStringParser->parseTypeString($expression);
        } catch (ParserException $e) {
            throw new \InvalidArgumentException(
                "Malformed type expression '$expression' in Filament attribute: {$e->getMessage()}",
                previous: $e,
            );
        }
    }
}
