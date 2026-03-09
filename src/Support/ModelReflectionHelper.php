<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Support;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class ModelReflectionHelper
{
    public const string RELATION_BASE = 'Illuminate\Database\Eloquent\Relations\Relation';

    public const string MODEL_BASE = 'Illuminate\Database\Eloquent\Model';

    public function __construct(
        protected ReflectionProvider $reflectionProvider,
    ) {}

    /**
     * Returns true if the method is a relationship, false if the method
     * exists but doesn't return a Relation, or null if the class can't
     * be resolved or the method doesn't exist (benefit of the doubt).
     *
     * Checks: 1) method-based (definitive), 2) @/property-read /Model-type (heuristic).
     * Also checks the plural form of the method name, since Filament
     * resolves both singular and plural relationship names in dot-notation.
     */
    public function isRelationship(string $modelClass, string $methodName, Scope $scope): ?bool
    {
        if (! $this->reflectionProvider->hasClass($modelClass)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($modelClass);
        $methodFound = false;

        foreach ([$methodName, $methodName.'s'] as $candidate) {
            if (! $classReflection->hasMethod($candidate)) {
                continue;
            }

            $methodFound = true;

            if ($this->methodReturnsRelation($classReflection, $candidate, $scope)) {
                return true;
            }
        }

        if ($methodFound) {
            return false;
        }

        // Fallback: @property-read with Model subclass type
        if ($this->propertyIsModelType($classReflection, $methodName, $scope)) {
            return true;
        }

        return null;
    }

    public function hasProperty(string $modelClass, string $propertyName): bool
    {
        if (! $this->reflectionProvider->hasClass($modelClass)) {
            return false;
        }

        return $this->reflectionProvider->getClass($modelClass)->hasInstanceProperty($propertyName);
    }

    /**
     * Resolve the object class name of a property's type.
     * e.g. Post has @/property EventOption|null $options → "EventOption"
     * e.g. Data object has public ?FormOptionMail $mail → "FormOptionMail"
     */
    public function resolvePropertyObjectType(string $className, string $propertyName, Scope $scope): ?string
    {
        $type = $this->getPropertyType($className, $propertyName, $scope);

        if ($type === null) {
            return null;
        }

        return TypeCombinator::removeNull($type)->getObjectClassNames()[0] ?? null;
    }

    /**
     * Resolve the item type of a collection/iterable property.
     * e.g. Collection<int, FilamentFormFieldBlock> → "FilamentFormFieldBlock"
     */
    public function resolveCollectionItemType(string $className, string $propertyName, Scope $scope): ?string
    {
        $type = $this->getPropertyType($className, $propertyName, $scope);

        if ($type === null) {
            return null;
        }

        $itemType = TypeCombinator::removeNull($type)->getIterableValueType();

        return $itemType->getObjectClassNames()[0] ?? null;
    }

    protected function getPropertyType(string $className, string $propertyName, ClassMemberAccessAnswerer $scope): ?Type
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (! $classReflection->hasInstanceProperty($propertyName)) {
            return null;
        }

        return $classReflection->getInstanceProperty($propertyName, $scope)->getReadableType();
    }

    public function hasMethod(string $modelClass, string $methodName): bool
    {
        return $this->reflectionProvider->hasClass($modelClass)
            && $this->reflectionProvider->getClass($modelClass)->hasMethod($methodName);
    }

    /**
     * Resolve the related model class from a relation method's generic return type.
     * e.g. Post::comments() returns HasMany<Comment, $this> → "Comment"
     *
     * Falls back to @/property-read /Model-type detection for users without typed methods.
     */
    public function resolveRelatedModel(string $modelClass, string $methodName, Scope $scope): ?string
    {
        return $this->doResolveRelatedModel($modelClass, $methodName, $scope);
    }

    /**
     * Scope-free variant for use during scanning (e.g. VirtualAnnotationProvider).
     * Uses OutOfClassScope — works for public relationship methods.
     */
    public function resolveRelatedModelStatically(string $modelClass, string $methodName): ?string
    {
        return $this->doResolveRelatedModel($modelClass, $methodName, new OutOfClassScope);
    }

    protected function doResolveRelatedModel(string $modelClass, string $methodName, ClassMemberAccessAnswerer $scope): ?string
    {
        if (! $this->reflectionProvider->hasClass($modelClass)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($modelClass);

        foreach ([$methodName, $methodName.'s'] as $candidate) {
            if (
                ! $classReflection->hasMethod($candidate)
                || ! $this->methodReturnsRelation($classReflection, $candidate, $scope)
            ) {
                continue;
            }

            $returnType = $classReflection->getMethod($candidate, $scope)
                ->getVariants()[0]
                ->getReturnType();

            $relatedType = $returnType->getTemplateType(self::RELATION_BASE, 'TRelatedModel');
            $relatedClasses = $relatedType->getObjectClassNames();

            // Skip base Model class (e.g. from morphTo) — can't determine the actual model
            if ($relatedClasses !== [] && $relatedClasses[0] !== self::MODEL_BASE) {
                return $relatedClasses[0];
            }
        }

        // Fallback: @property-read with Model subclass type
        return $this->resolvePropertyModelType($classReflection, $methodName, $scope);
    }

    protected function methodReturnsRelation(ClassReflection $classReflection, string $methodName, ClassMemberAccessAnswerer $scope): bool
    {
        $returnType = $classReflection->getMethod($methodName, $scope)
            ->getVariants()[0]
            ->getReturnType();

        // Use !no() to handle union types like HasMany|_IH_QueryBuilder — if any part is a Relation, it counts
        return ! new ObjectType(self::RELATION_BASE)->isSuperTypeOf($returnType)->no();
    }

    /**
     * Check if a property's type is a Model subclass (e.g. @/property-read Author|null $author).
     */
    protected function propertyIsModelType(ClassReflection $classReflection, string $propertyName, ClassMemberAccessAnswerer $scope): bool
    {
        return $this->resolvePropertyModelType($classReflection, $propertyName, $scope) !== null;
    }

    /**
     * Resolve the model class from a @/property-read type.
     * e.g. @/property-read Author|null $reviewer → "Author"
     */
    protected function resolvePropertyModelType(ClassReflection $classReflection, string $propertyName, ClassMemberAccessAnswerer $scope): ?string
    {
        if (! $classReflection->hasInstanceProperty($propertyName)) {
            return null;
        }

        $type = TypeCombinator::removeNull(
            $classReflection->getInstanceProperty($propertyName, $scope)->getReadableType()
        );

        if (! $this->isModelSubclass($type)) {
            return null;
        }

        $classNames = $type->getObjectClassNames();

        if ($classNames === [] || $classNames[0] === self::MODEL_BASE) {
            return null;
        }

        return $classNames[0];
    }

    protected function isModelSubclass(Type $type): bool
    {
        return new ObjectType(self::MODEL_BASE)->isSuperTypeOf($type)->yes();
    }
}
