<?php

/** @noinspection ClassConstantCanBeUsedInspection */

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Support;

use PHPStan\Reflection\ReflectionProvider;
use ReflectionException;

class FilamentClassHelper
{
    private const string RESOURCE_BASE = 'Filament\Resources\Resource';

    private const string EDIT_RECORD = 'Filament\Resources\Pages\EditRecord';

    private const string CREATE_RECORD = 'Filament\Resources\Pages\CreateRecord';

    private const string LIST_RECORDS = 'Filament\Resources\Pages\ListRecords';

    private const string VIEW_RECORD = 'Filament\Resources\Pages\ViewRecord';

    private const string MANAGE_RELATED = 'Filament\Resources\Pages\ManageRelatedRecords';

    private const string RELATION_MANAGER = 'Filament\Resources\RelationManagers\RelationManager';

    private const string FORM_FIELD_BASE = 'Filament\Forms\Components\Field';

    private const string TABLE_COLUMN_BASE = 'Filament\Tables\Columns\Column';

    private const string SCHEMA_COMPONENT_BASE = 'Filament\Schemas\Components\Component';

    private const string INFOLIST_ENTRY_BASE = 'Filament\Infolists\Components\Entry';

    private const string ACTION_BASE = 'Filament\Actions\Action';

    private const array RESOURCE_PAGES = [
        self::EDIT_RECORD,
        self::CREATE_RECORD,
        self::LIST_RECORDS,
        self::VIEW_RECORD,
        self::MANAGE_RELATED,
    ];

    public function __construct(
        protected readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function isResourceClass(string $className): bool
    {
        return $this->isOrExtendsClass($className, self::RESOURCE_BASE);
    }

    public function isResourcePage(string $className): bool
    {
        return array_any(self::RESOURCE_PAGES, fn ($pageClass) => $this->isOrExtendsClass($className, $pageClass));
    }

    public function isRelationManager(string $className): bool
    {
        return $this->isOrExtendsClass($className, self::RELATION_MANAGER);
    }

    public function isManageRelatedRecords(string $className): bool
    {
        return $this->isOrExtendsClass($className, self::MANAGE_RELATED);
    }

    public function isFormField(string $className): bool
    {
        return $this->isOrExtendsClass($className, self::FORM_FIELD_BASE);
    }

    public function isTableColumn(string $className): bool
    {
        return $this->isOrExtendsClass($className, self::TABLE_COLUMN_BASE);
    }

    public function isInfolistEntry(string $className): bool
    {
        return $this->isOrExtendsClass($className, self::INFOLIST_ENTRY_BASE);
    }

    public function isAction(string $className): bool
    {
        return $this->isOrExtendsClass($className, self::ACTION_BASE);
    }

    /**
     * Matches: Entry, Field, Section, Group, Split, Tabs, and other layout components.
     * Does NOT match: Column, Action, Filter.
     *
     * @see \Filament\Schemas\Components\Component
     */
    public function isSchemaComponent(string $className): bool
    {
        return $this->isOrExtendsClass($className, self::SCHEMA_COMPONENT_BASE);
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

    /**
     * Infer the resource class from a schema/table helper class namespace.
     *
     * Convention: App\...\ScannerAuths\Schemas\ScannerAuthForm → App\...\ScannerAuths\ScannerAuthResource
     */
    public function inferResourceFromNamespace(string $className): ?string
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $namespace = $this->reflectionProvider->getClass($className)->getNativeReflection()->getNamespaceName();
        $segments = NamespaceHelper::splitSegments($namespace);
        $subDirectory = array_pop($segments);

        if (! in_array($subDirectory, ['Schemas', 'Tables', 'Pages'], true)) {
            return null;
        }

        $parentNamespace = NamespaceHelper::joinSegments($segments);
        $resourceDirectory = end($segments);

        foreach ([$resourceDirectory, rtrim($resourceDirectory, 's')] as $stem) {
            $candidate = NamespaceHelper::prependNamespace($parentNamespace, $stem.'Resource');

            if ($this->reflectionProvider->hasClass($candidate) && $this->isResourceClass($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Find resource page classes by naming convention.
     *
     * @return list<string>
     */
    public function resolveResourcePages(string $resourceClass): array
    {
        $lastBackslash = strrpos($resourceClass, '\\');
        if ($lastBackslash === false) {
            return [];
        }

        $shortName = substr($resourceClass, $lastBackslash + 1);
        $stem = preg_replace('/Resource$/', '', $shortName);
        $namespace = substr($resourceClass, 0, $lastBackslash);
        $pagesNamespace = $namespace.'\\Pages\\';

        $candidates = [
            $pagesNamespace.'Create'.$stem,
            $pagesNamespace.'Edit'.$stem,
            $pagesNamespace.'List'.$stem.'s',
            $pagesNamespace.'View'.$stem,
            $pagesNamespace.'Manage'.$stem,
        ];

        $pages = [];
        foreach ($candidates as $candidate) {
            if ($this->reflectionProvider->hasClass($candidate) && $this->isResourcePage($candidate)) {
                $pages[] = $candidate;
            }
        }

        return $pages;
    }

    public function hasOptions(string $className): bool
    {
        return $this->usesTrait($className, 'Filament\Forms\Components\Concerns\HasOptions');
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
