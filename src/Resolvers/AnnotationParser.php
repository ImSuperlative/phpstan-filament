<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Resolvers;

use ImSuperlative\PhpstanFilament\Data\FilamentFieldAnnotation;
use ImSuperlative\PhpstanFilament\Data\FilamentModelAnnotation;
use ImSuperlative\PhpstanFilament\Data\FilamentPageAnnotation;
use ImSuperlative\PhpstanFilament\Data\FilamentStateAnnotation;
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
