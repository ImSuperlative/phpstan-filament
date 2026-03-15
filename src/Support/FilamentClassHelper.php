<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Support;

use ImSuperlative\PhpstanFilament\Support\FilamentComponent as FC;
use PHPStan\Reflection\ReflectionProvider;
use ReflectionException;

class FilamentClassHelper
{
    public function __construct(
        protected readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function isResourceClass(string $className): bool
    {
        return $this->isOrExtendsClass($className, FC::RESOURCE);
    }

    public function isResourcePage(string $className): bool
    {
        return array_any(FC::RESOURCE_PAGES, fn ($pageClass) => $this->isOrExtendsClass($className, $pageClass));
    }

    public function isRelationManager(string $className): bool
    {
        return $this->isOrExtendsClass($className, FC::RELATION_MANAGER);
    }

    public function isManageRelatedRecords(string $className): bool
    {
        return $this->isOrExtendsClass($className, FC::MANAGE_RELATED_RECORDS);
    }

    public function isFormField(string $className): bool
    {
        return $this->isOrExtendsClass($className, FC::FORM_FIELD);
    }

    public function isTableColumn(string $className): bool
    {
        return $this->isOrExtendsClass($className, FC::TABLE_COLUMN);
    }

    public function isInfolistEntry(string $className): bool
    {
        return $this->isOrExtendsClass($className, FC::INFOLIST_ENTRY);
    }

    public function isAction(string $className): bool
    {
        return $this->isOrExtendsClass($className, FC::ACTION);
    }

    /**
     * Matches: Entry, Field, Section, Group, Split, Tabs, and other layout components.
     * Does NOT match: Column, Action, Filter.
     *
     * @see \Filament\Schemas\Components\Component
     */
    public function isSchemaComponent(string $className): bool
    {
        return $this->isOrExtendsClass($className, FC::SCHEMA_COMPONENT);
    }

    // --- Composite checks ---

    /**
     * Resource pages and relation managers — classes scoped to a resource.
     */
    public function isResourceScoped(string $className): bool
    {
        return $this->isResourcePage($className)
            || $this->isRelationManager($className);
    }

    /**
     * Classes that manage nested/related records (ManageRelatedRecords page or RelationManager).
     */
    public function isNestedResource(string $className): bool
    {
        return $this->isManageRelatedRecords($className)
            || $this->isRelationManager($className);
    }

    /**
     * Schema components + table columns. Covers everything that renders
     * inside a resource page (entries, fields, columns, layouts).
     * Does NOT match: Action, Filter.
     */
    public function isFilamentComponent(string $className): bool
    {
        return $this->isSchemaComponent($className)
            || $this->isFieldComponent($className);
    }

    /**
     * Components that have a make('fieldName') tied to model data.
     * Matches: Column, Entry, Field (TextInput, Select, etc.)
     */
    public function isFieldComponent(string $className): bool
    {
        return $this->isTableColumn($className)
            || $this->isInfolistEntry($className)
            || $this->isFormField($className);
    }

    /**
     * Read-only components that display model data.
     * Matches: Column, Entry.
     */
    public function isDisplayComponent(string $className): bool
    {
        return $this->isTableColumn($className)
            || $this->isInfolistEntry($className);
    }

    public function isClosureSupported(string $className): bool
    {
        return $this->isSchemaComponent($className)
            || $this->isTableColumn($className)
            || $this->isAction($className);
    }

    public function isInjectionSupported(string $className): bool
    {
        return $this->isSchemaComponent($className)
            || $this->isTableColumn($className)
            || $this->isAction($className);
    }

    public function readStaticProperty(string $className, string $property): ?string
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        try {
            $prop = $this->reflectionProvider->getClass($className)->getNativeReflection()->getProperty($property);

            $value = $prop->getDefaultValue();

            return is_string($value) ? $value : null;
        } catch (ReflectionException) {
            return null;
        }
    }

    public function hasOptions(string $className): bool
    {
        return $this->usesTrait($className, FC::HAS_OPTIONS);
    }

    protected function usesTrait(string $className, string $traitClass): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        return in_array($traitClass, class_uses_recursive($className), true);
    }

    protected function isOrExtendsClass(string $className, string $targetClass): bool
    {
        if ($className === $targetClass) {
            return true;
        }

        if (! $this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $parents = $this->reflectionProvider->getClass($className)->getParentClassesNames();

        return in_array($targetClass, $parents, true);
    }
}
