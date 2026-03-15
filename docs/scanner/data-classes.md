# Data Classes

All scanner data lives in `src/Data/Scanner/`. These are simple DTOs — most are typed maps wrapping an array with the `HasTypedMap` trait.

## ProjectScanResult

The container that flows through the entire pipeline. Transformers read from it and write to it.

```php
final class ProjectScanResult implements JsonSerializable
{
    /** @var array<class-string, object> */
    protected array $attributes = [];

    public function __construct(
        public readonly array $index,   // filePath => FileMetadata
        public readonly array $roots,   // file paths of resource roots
    ) {}
}
```

**Core methods:**

```php
// Store a typed attribute (keyed by class name automatically)
$result->set(new ResourcePages($data));

// Retrieve (throws if missing)
$pages = $result->get(ResourcePages::class);

// Retrieve (nullable)
$pages = $result->find(ResourcePages::class);

// Check existence
$result->has(ResourcePages::class);
```

The attribute bag uses the class name as key, so you can only store one instance per type. This is intentional — each transformer produces exactly one output type.

## HasTypedMap Trait

Most scanner data classes use this trait for consistent map access:

```php
/**
 * @template TKey of string
 * @template TValue
 */
trait HasTypedMap
{
    /** @var array<TKey, TValue> */
    protected readonly array $data;

    /** @return TValue|null */
    public function get(string $key): mixed;

    public function has(string $key): bool;

    /** @return array<TKey, TValue> */
    public function all(): array;

    /**
     * Get a value and wrap it in a provider object.
     * @return TProvider|null
     */
    public function into(string $key, string $providerClass): mixed;

    /**
     * Wrap all values in provider objects.
     * @return array<TKey, TProvider>
     */
    public function mapInto(string $providerClass): array;
}
```

The `into()` method is how `FilamentProjectIndex` wraps raw `ComponentNode` data into `ComponentProvider`:

```php
// FilamentProjectIndex::getComponent()
return $this->scanResult()
    ->find(ComponentContext::class)
    ?->into($class, ComponentProvider::class);
// Equivalent to: new ComponentProvider($componentContext->get($class))
```

## FileMetadata

Extracted from each PHP file during indexing. Lightweight — no reflection, pure AST.

```php
final readonly class FileMetadata
{
    public function __construct(
        public string $fullyQualifiedName,  // e.g. 'App\Filament\Resources\Employees\EmployeeResource'
        public ?string $extends,            // e.g. 'Filament\Resources\Resource'
        public array $traits,               // e.g. ['App\Concerns\HasSomeFeature']
        public array $useMap,               // e.g. ['Employee' => 'App\Models\Employee']
        public ?string $namespace,          // e.g. 'App\Filament\Resources\Employees'
        public bool $isTrait,               // true for traits, false for classes
    ) {}
}
```

## Graph Phase Outputs

### ResourcePages

Maps each resource to its registered pages (slug => FQCN).

```php
// Type: array<ResourceFqcn, array<slug, PageFqcn>>
new ResourcePages([
    'App\\..\EmployeeResource' => [
        'index'    => 'App\\..\ListEmployees',
        'create'   => 'App\\..\CreateEmployee',
        'edit'     => 'App\\..\EditEmployee',
        'view'     => 'App\\..\ViewEmployee',
        'projects' => 'App\\..\ManageEmployeeProjects',
        'chart'    => 'App\\..\OrganizationChart',
    ],
    'App\\..\OrganizationResource' => [
        'index'  => 'App\\..\ListOrganizations',
        'create' => 'App\\..\CreateOrganization',
        'edit'   => 'App\\..\EditOrganization',
        'view'   => 'App\\..\ViewOrganization',
    ],
]);
```

**Access:**

```php
$pages = $result->get(ResourcePages::class);
$pages->get('App\\..\EmployeeResource');
// => ['index' => 'App\\..\ListEmployees', ...]
```

### ResourceRelations

Maps each resource to its relation manager FQCNs.

```php
// Type: array<ResourceFqcn, list<ManagerFqcn>>
new ResourceRelations([
    'App\\..\EmployeeResource' => [
        'App\\..\RolesRelationManager',
    ],
    'App\\..\OrganizationResource' => [
        'App\\..\DepartmentsRelationManager',
        'App\\..\LocationsRelationManager',
    ],
]);
```

### ResourceModels

Maps resources, pages, and relation managers to their Eloquent model.

```php
// Type: array<ComponentFqcn, ModelFqcn>
new ResourceModels([
    'App\\..\EmployeeResource'       => 'App\Models\Employee',
    'App\\..\ListEmployees'          => 'App\Models\Employee',
    'App\\..\EditEmployee'           => 'App\Models\Employee',
    'App\\..\ManageEmployeeProjects' => 'App\Models\Project',
    'App\\..\RolesRelationManager'   => 'App\Models\Role',
    'App\\..\OrganizationResource'   => 'App\Models\Organization',
    // ...
]);
```

## Discovery Phase Outputs

### ComponentToResources

Maps every discovered component to the resource(s) that own it.

```php
// Type: array<ComponentFqcn, list<ResourceFqcn>>
new ComponentToResources([
    'App\\..\EmployeeResource'  => ['App\\..\EmployeeResource'],
    'App\\..\EditEmployee'      => ['App\\..\EmployeeResource'],
    'App\\..\EmployeeForm'      => ['App\\..\EmployeeResource'],
    'App\\..\EmployeesTable'    => ['App\\..\EmployeeResource'],
    'App\\..\RolesRelationManager' => ['App\\..\EmployeeResource'],
    // ...
]);
```

A component owned by multiple resources appears with multiple entries in its list — this is what triggers the `Shared` tag.

### DependencyGraph

The class dependency adjacency list built during BFS.

```php
// Type: array<SourceFqcn, list<TargetFqcn>>
new DependencyGraph([
    'App\\..\EmployeeResource' => [
        'App\\..\EmployeeForm',
        'App\\..\EmployeesTable',
        'App\\..\EmployeeInfolist',
    ],
    'App\\..\EditEmployee' => [
        'App\\..\EmployeeForm',
    ],
]);
```

Dependencies include static `::configure()` and `::make()` calls, extends, and trait usage.

## Property Phase Output

### ComponentDeclarations

Maps components to their parsed class property values.

```php
// Type: array<ComponentFqcn, ComponentDeclaration>
new ComponentDeclarations([
    'App\\..\EmployeeResource' => new ComponentDeclaration(
        model: 'App\Models\Employee',
    ),
    'App\\..\EditEmployee' => new ComponentDeclaration(
        resourceClass: 'App\\..\EmployeeResource',
    ),
    'App\\..\ManageEmployeeProjects' => new ComponentDeclaration(
        model: 'App\Models\Project',
        resourceClass: 'App\\..\EmployeeResource',
        relatedResourceClass: 'App\\..\ProjectResource',
        relationshipName: 'projects',
    ),
    'App\\..\DepartmentResource' => new ComponentDeclaration(
        model: 'App\Models\Department',
        parentResource: 'App\\..\OrganizationResource',
    ),
    'App\\..\BudgetResource' => new ComponentDeclaration(
        model: 'App\Models\Budget',
        cluster: 'App\\..\FinanceCluster',
    ),
]);
```

### ComponentDeclaration

```php
final readonly class ComponentDeclaration
{
    public function __construct(
        public ?string $model = null,
        public ?string $resourceClass = null,
        public ?string $relatedResourceClass = null,
        public ?string $relationshipName = null,
        public ?string $cluster = null,
        public ?string $parentResource = null,
    ) {}
}
```

## Enrichment Phase Outputs

### ComponentAnnotations

Maps components to their explicit PHPDoc/attribute annotations. Only components with annotations are included.

```php
// Type: array<ComponentFqcn, ExplicitAnnotations>
new ComponentAnnotations([
    'App\\..\SharedPostForm' => new ExplicitAnnotations(
        model: 'App\Models\Post',
        pageModels: [],
        states: ['title', 'content'],
        fields: [],
    ),
]);
```

### ExplicitAnnotations

```php
final readonly class ExplicitAnnotations
{
    public function __construct(
        public ?string $model = null,          // from @filament-model
        public array $pageModels = [],         // from @filament-page (page FQCN => model|null)
        public array $states = [],             // from @filament-state (field names)
        public array $fields = [],             // from @filament-field (field names)
    ) {}
}
```

### ComponentContext

The final output — maps every component to its synthesized `ComponentNode`.

```php
// Type: array<ComponentFqcn, ComponentNode>
new ComponentContext([
    'App\\..\EmployeeResource' => new ComponentNode(
        tags: [ComponentTag::Resource],
        explicitModel: null,
        pageModels: [/* page => model map */],
        resourceModels: ['App\\..\EmployeeResource' => 'App\Models\Employee'],
        resourcePages: ['App\\..\EmployeeResource' => [/* page FQCNs */]],
        owningResources: ['App\\..\EmployeeResource'],
        declaration: new ComponentDeclaration(model: 'App\Models\Employee'),
    ),
    // ... one entry per discovered component
]);
```

### ComponentNode

```php
final readonly class ComponentNode
{
    public function __construct(
        public array $tags = [],              // list<ComponentTag>
        public ?string $explicitModel = null, // from @filament-model
        public array $pageModels = [],        // page FQCN => model FQCN|null
        public array $resourceModels = [],    // resource FQCN => model FQCN|null
        public array $resourcePages = [],     // resource FQCN => list<page FQCNs>
        public array $owningResources = [],   // list<resource FQCNs>
        public ComponentDeclaration $declaration = new ComponentDeclaration,
    ) {}
}
```

### ComponentTag

```php
enum ComponentTag: string
{
    // Primary types (exactly one per component)
    case Resource = 'resource';
    case Page = 'page';
    case RelationManager = 'relationManager';
    case Component = 'component';

    // Page subtypes (in addition to Page)
    case EditPage = 'editPage';
    case CreatePage = 'createPage';
    case ListRecords = 'listRecords';
    case ViewRecord = 'viewRecord';
    case ManageRelatedRecords = 'manageRelatedRecords';

    // Modifiers (zero or more)
    case Nested = 'nested';
    case Clustered = 'clustered';
    case Shared = 'shared';
}
```

## ComponentProvider

Not a data class, but the primary consumer-facing wrapper around `ComponentNode`. Located at `src/Resolvers/ComponentProvider.php`.

```php
final class ComponentProvider
{
    public function __construct(protected ComponentNode $node) {}

    // Model resolution
    public function getModel(): ?string;           // Single unambiguous model, or null
    public function getModelClasses(): array;       // All possible models
    public function getModelForPage(string $page): ?string;
    public function getModelForResource(string $resource): ?string;
    public function getOwnerModel(): ?string;       // Model of the owning resource

    // Structure queries
    public function getPages(): array;              // All page FQCNs
    public function getResources(): array;          // All resource FQCNs
    public function getOwningResources(): array;    // Resources that own this component
    public function getPagesForResource(string $resource): array;

    // Declaration properties
    public function getResourceClass(): ?string;
    public function getRelatedResourceClass(): ?string;
    public function getRelationshipName(): ?string;

    // Tag queries
    public function hasTag(ComponentTag $tag): bool;
    public function hasAnyTag(array $tags): bool;
    public function isNested(): bool;               // Nested or ManageRelatedRecords
}
```

**Usage from rules/extensions:**

```php
$provider = $this->filamentProjectIndex->getComponent($className);

if ($provider === null) {
    return; // Not a Filament component
}

$model = $provider->getModel();
$isEditPage = $provider->hasTag(ComponentTag::EditPage);
$ownerModel = $provider->getOwnerModel();
```

## Next

- [Extending](extending.md) — how to add new transformers that produce new data classes