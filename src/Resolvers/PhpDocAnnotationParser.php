<?php

namespace ImSuperlative\FilamentPhpstan\Resolvers;

use ImSuperlative\FilamentPhpstan\Data\FilamentFieldAnnotation;
use ImSuperlative\FilamentPhpstan\Data\FilamentModelAnnotation;
use ImSuperlative\FilamentPhpstan\Data\FilamentPageAnnotation;
use ImSuperlative\FilamentPhpstan\Data\FilamentStateAnnotation;
use ImSuperlative\FilamentPhpstan\Data\FilamentTagAnnotation;
use ImSuperlative\FilamentPhpstan\Parser\TypeStringParser;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Parser\TokenIterator;

final class PhpDocAnnotationParser
{
    public function __construct(
        protected readonly TypeStringParser $typeStringParser,
    ) {}

    public function readModelAnnotation(string $phpDoc): ?FilamentModelAnnotation
    {
        return ($this->parseTypedTags($this->tagValues($phpDoc, '@filament-model'))[0] ?? null)
            ?->toModelAnnotation();
    }

    /** @return array<FilamentPageAnnotation> */
    public function readPageAnnotations(string $phpDoc): array
    {
        return array_map(
            fn (FilamentTagAnnotation $tag) => $tag->toPageAnnotation(),
            $this->parseTypedTags($this->tagValues($phpDoc, '@filament-page')),
        );
    }

    /** @return array<FilamentStateAnnotation> */
    public function readStateAnnotations(string $phpDoc): array
    {
        return array_map(
            fn (FilamentTagAnnotation $tag) => $tag->toStateAnnotation(),
            $this->parseTypedTags($this->tagValues($phpDoc, '@filament-state')),
        );
    }

    /** @return array<FilamentFieldAnnotation> */
    public function readFieldAnnotations(string $phpDoc): array
    {
        return array_map(
            fn (FilamentTagAnnotation $tag) => $tag->toFieldAnnotation(),
            $this->parseTypedTags($this->tagValues($phpDoc, '@filament-field')),
        );
    }

    protected function parseTypedTag(string $value): FilamentTagAnnotation
    {
        $tokens = $this->typeStringParser->tokenize($value);

        return new FilamentTagAnnotation(
            type: $this->typeStringParser->getTypeParser()->parse($tokens),
            fieldName: $this->getOptionalFieldName($tokens),
        );
    }

    /**
     * @param  list<string>  $values
     * @return array<FilamentTagAnnotation>
     */
    protected function parseTypedTags(array $values): array
    {
        return array_map(
            fn (string $value) => $this->parseTypedTag($value),
            $values,
        );
    }

    protected function getOptionalFieldName(TokenIterator $tokens): ?string
    {
        $remaining = trim($tokens->currentTokenValue());

        return ($remaining === '' || $remaining === '*') ? null : $remaining;
    }

    /**
     * @return list<string>
     */
    protected function tagValues(string $phpDoc, string $tagName): array
    {
        /** @var list<string> */
        return array_reduce(
            $this->parse($phpDoc)->getTagsByName($tagName),
            static function (array $carry, $tag) {
                if ($tag->value instanceof GenericTagValueNode && trim($tag->value->value) !== '') {
                    $carry[] = trim($tag->value->value);
                }

                return $carry;
            }, []
        );
    }

    protected function parse(string $phpDoc): PhpDocNode
    {
        return $this->typeStringParser->parsePhpDoc($phpDoc);
    }
}
