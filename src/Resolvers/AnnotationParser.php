<?php

namespace ImSuperlative\FilamentPhpstan\Resolvers;

use ImSuperlative\FilamentPhpstan\Data\FilamentFieldAnnotation;
use ImSuperlative\FilamentPhpstan\Data\FilamentModelAnnotation;
use ImSuperlative\FilamentPhpstan\Data\FilamentPageAnnotation;
use ImSuperlative\FilamentPhpstan\Data\FilamentStateAnnotation;
use PHPStan\Reflection\ClassReflection;

interface AnnotationParser
{
    public function readModelAnnotation(ClassReflection $class, ?string $method = null): ?FilamentModelAnnotation;

    /** @return array<FilamentPageAnnotation> */
    public function readPageAnnotations(ClassReflection $class, ?string $method = null): array;

    /** @return array<FilamentStateAnnotation> */
    public function readStateAnnotations(ClassReflection $class, ?string $method = null): array;

    /** @return array<FilamentFieldAnnotation> */
    public function readFieldAnnotations(ClassReflection $class, ?string $method = null): array;
}
