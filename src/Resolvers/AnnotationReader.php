<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Resolvers;

use ImSuperlative\FilamentPhpstan\Data\FilamentFieldAnnotation;
use ImSuperlative\FilamentPhpstan\Data\FilamentModelAnnotation;
use ImSuperlative\FilamentPhpstan\Data\FilamentPageAnnotation;
use ImSuperlative\FilamentPhpstan\Data\FilamentStateAnnotation;
use ImSuperlative\FilamentPhpstan\Data\FilamentTagAnnotation;
use PHPStan\Reflection\ClassReflection;

final class AnnotationReader implements AnnotationParser
{
    public function __construct(
        protected readonly AttributeAnnotationParser $attributeParser,
        protected readonly PhpDocAnnotationParser $phpDocParser,
    ) {}

    public function readModelAnnotation(ClassReflection $class, ?string $method = null): ?FilamentModelAnnotation
    {
        return $this->attributeParser->readModelAnnotation($class, $method)
            ?? $this->readPhpDocModel($class, $method);
    }

    /** @return array<FilamentPageAnnotation> */
    public function readPageAnnotations(ClassReflection $class, ?string $method = null): array
    {
        $fromAttributes = $this->attributeParser->readPageAnnotations($class, $method);
        $fromPhpDoc = $this->readPhpDocPages($class, $method);

        return $this->mergeAnnotations($fromAttributes, $fromPhpDoc);
    }

    /** @return array<FilamentStateAnnotation> */
    public function readStateAnnotations(ClassReflection $class, ?string $method = null): array
    {
        $fromAttributes = $this->attributeParser->readStateAnnotations($class, $method);
        $fromPhpDoc = $this->readPhpDocStates($class, $method);

        return $this->mergeAnnotations($fromAttributes, $fromPhpDoc);
    }

    /** @return array<FilamentFieldAnnotation> */
    public function readFieldAnnotations(ClassReflection $class, ?string $method = null): array
    {
        $fromAttributes = $this->attributeParser->readFieldAnnotations($class, $method);
        $fromPhpDoc = $this->readPhpDocFields($class, $method);

        return $this->mergeAnnotations($fromAttributes, $fromPhpDoc);
    }

    protected function getPhpDoc(ClassReflection $class, ?string $method): ?string
    {
        if ($method !== null) {
            $methodReflection = $class->getNativeReflection()->getMethod($method);

            return $methodReflection->getDocComment() ?: null;
        }

        return $class->getNativeReflection()->getDocComment() ?: null;
    }

    protected function readPhpDocModel(ClassReflection $class, ?string $method): ?FilamentModelAnnotation
    {
        $phpDoc = $this->getPhpDoc($class, $method);

        return $phpDoc !== null ? $this->phpDocParser->readModelAnnotation($phpDoc) : null;
    }

    /** @return array<FilamentPageAnnotation> */
    protected function readPhpDocPages(ClassReflection $class, ?string $method): array
    {
        $phpDoc = $this->getPhpDoc($class, $method);

        return $phpDoc !== null ? $this->phpDocParser->readPageAnnotations($phpDoc) : [];
    }

    /** @return array<FilamentStateAnnotation> */
    protected function readPhpDocStates(ClassReflection $class, ?string $method): array
    {
        $phpDoc = $this->getPhpDoc($class, $method);

        return $phpDoc !== null ? $this->phpDocParser->readStateAnnotations($phpDoc) : [];
    }

    /** @return array<FilamentFieldAnnotation> */
    protected function readPhpDocFields(ClassReflection $class, ?string $method): array
    {
        $phpDoc = $this->getPhpDoc($class, $method);

        return $phpDoc !== null ? $this->phpDocParser->readFieldAnnotations($phpDoc) : [];
    }

    /**
     * @template T of FilamentTagAnnotation
     *
     * @param  array<T>  $fromAttributes
     * @param  array<T>  $fromPhpDoc
     * @return array<T>
     */
    protected function mergeAnnotations(array $fromAttributes, array $fromPhpDoc): array
    {
        if ($fromPhpDoc === []) {
            return $fromAttributes;
        }

        if ($fromAttributes === []) {
            return $fromPhpDoc;
        }

        $seen = [];
        foreach ($fromAttributes as $annotation) {
            $seen[] = $annotation->typeAsString().'::'.($annotation->fieldName ?? '*');
        }

        $merged = $fromAttributes;
        foreach ($fromPhpDoc as $annotation) {
            $key = $annotation->typeAsString().'::'.($annotation->fieldName ?? '*');
            if (! in_array($key, $seen, true)) {
                $merged[] = $annotation;
                $seen[] = $key;
            }
        }

        return $merged;
    }
}
