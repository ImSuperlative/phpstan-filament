# Transformers

Transformers are the processing units of the scanner pipeline. Each transformer reads from `ProjectScanResult`, does its work, and writes its output back to the result.

## Transformer Hierarchy

```
ScanTransformer (interface)
│   transform(ProjectScanResult): ProjectScanResult
│
├── GraphTransformer (interface, extends ScanTransformer)
│   │   componentMappings(ProjectScanResult): array<ResourceFqcn, list<ComponentFqcn>>
│   │
│   ├── PagesTransformer
│   ├── RelationsTransformer
│   └── ModelTransformer
│
└── EnrichmentTransformer (interface, extends ScanTransformer)
    │   (no additional methods)
    │
    ├── AnnotationTransformer
    └── ComponentContextTransformer
```

### `ScanTransformer` (interface)

The base contract. A single method:

```php
interface ScanTransformer
{
    public function transform(ProjectScanResult $result): ProjectScanResult;
}
```

### `GraphTransformer` (interface)

Extends `ScanTransformer` with `componentMappings()` — returns direct resource-to-component links that `ComponentDiscovery` uses as starting points for its graph walk:

```php
interface GraphTransformer extends ScanTransformer
{
    /** @return array<class-string, list<class-string>> resource FQCN => component FQCNs */
    public function componentMappings(ProjectScanResult $result): array;
}
```

Graph transformers run **before** `ComponentDiscovery` and `PropertyReader`.

### `EnrichmentTransformer` (interface)

A marker interface — no additional methods beyond `transform()`:

```php
interface EnrichmentTransformer extends ScanTransformer {}
```

Enrichment transformers run **after** discovery and property reading, so they have access to `ComponentToResources`, `ComponentDeclarations`, and all graph transformer outputs.

---

## Graph Transformers

### PagesTransformer

**Purpose:** Extract page registrations from each resource's `getPages()` method.

**Input:** `$result->index` (file metadata) + `$result->roots` (resource file paths)

**Output:** `ResourcePages` — `array<ResourceFqcn, array<slug, PageFqcn>>`

**Algorithm:**

1. For each root resource file, parse the `getPages()` method AST
2. Find the returned array
3. For each item with a string key (the slug), resolve the `PageClass::route(...)` static call
4. Resolve the page class name to a fully qualified name using the file's import map

**Example input (EmployeeResource):**

```php
public static function getPages(): array
{
    return [
        'index' => ListEmployees::route('/'),
        'create' => CreateEmployee::route('/create'),
        'view' => ViewEmployee::route('/{record}'),
        'edit' => EditEmployee::route('/{record}/edit'),
        'projects' => ManageEmployeeProjects::route('/{record}/projects'),
        'chart' => OrganizationChart::route('/chart'),
    ];
}
```

**Example output:**

```php
ResourcePages([
    'App\\..\EmployeeResource' => [
        'index'    => 'App\\..\Pages\ListEmployees',
        'create'   => 'App\\..\Pages\CreateEmployee',
        'view'     => 'App\\..\Pages\ViewEmployee',
        'edit'     => 'App\\..\Pages\EditEmployee',
        'projects' => 'App\\..\Pages\ManageEmployeeProjects',
        'chart'    => 'App\\..\Pages\OrganizationChart',
    ],
])
```

**componentMappings() output:**

```php
[
    'App\\..\EmployeeResource' => [
        'App\\..\Pages\ListEmployees',
        'App\\..\Pages\CreateEmployee',
        // ... all page FQCNs (values only, slugs stripped)
    ],
]
```

---

### RelationsTransformer

**Purpose:** Extract relation manager registrations from each resource's `getRelations()` method.

**Input:** `$result->index` + `$result->roots`

**Output:** `ResourceRelations` — `array<ResourceFqcn, list<ManagerFqcn>>`

**Algorithm:**

1. For each root resource file, parse the `getRelations()` method AST
2. Find the returned array
3. Resolve each item, handling three patterns:
   - **Class reference:** `RolesRelationManager::class`
   - **RelationGroup:** `RelationGroup::make('label', [Manager1::class, Manager2::class])`
   - **RelationManagerConfiguration:** `RelationManagerConfiguration::make(Manager::class, ...)`

**Example input (OrganizationResource):**

```php
public static function getRelations(): array
{
    return [
        RelationGroup::make('Structure', [
            DepartmentsRelationManager::class,
            LocationsRelationManager::class,
        ]),
    ];
}
```

**Example output:**

```php
ResourceRelations([
    'App\\..\OrganizationResource' => [
        'App\\..\RelationManagers\DepartmentsRelationManager',
        'App\\..\RelationManagers\LocationsRelationManager',
    ],
])
```

**componentMappings() output:** Same structure — managers are returned as direct components of their resource.

---

### ModelTransformer

**Purpose:** Resolve the Eloquent model for each resource, page, and relation manager.

**Input:** `$result->index`, `$result->roots`, `ResourcePages`, `ResourceRelations`

**Output:** `ResourceModels` — `array<ComponentFqcn, ModelFqcn>`

**Algorithm — three passes:**

#### Pass 1: Resource models

For each root resource, try in order:

1. **PHPStan reflection** — check `getModel()` return type
2. **AST parsing** — parse literal return statement in `getModel()`
3. **Static property** — read `protected static string $model`

```php
// EmployeeResource.php
protected static ?string $model = Employee::class;
// → 'App\Models\Employee'
```

#### Pass 2: Page models

For each page of each resource:

- **Standard pages** (Edit, Create, List, View) inherit the parent resource's model
- **ManageRelatedRecords pages** use a different resolution:
  1. Read `$relationship` property, look up the relationship method on the parent model
  2. Try `$relatedResource` property, look up that resource's model
  3. Fall back to parent resource model

```php
// ManageEmployeeProjects.php
protected static string $relationship = 'projects';
// Employee::projects() returns HasMany<Project>
// → 'App\Models\Project'
```

#### Pass 3: Relation manager models

For each relation manager:

1. Read `$relationship` property, look up the relationship method on the parent model
2. Try `$relatedResource` property
3. Fall back to parent resource model

```php
// RolesRelationManager.php
protected static string $relationship = 'roles';
// Employee::roles() returns BelongsToMany<Role>
// → 'App\Models\Role'
```

**Example output:**

```php
ResourceModels([
    'App\\..\EmployeeResource'          => 'App\Models\Employee',
    'App\\..\ListEmployees'             => 'App\Models\Employee',
    'App\\..\EditEmployee'              => 'App\Models\Employee',
    'App\\..\CreateEmployee'            => 'App\Models\Employee',
    'App\\..\ViewEmployee'              => 'App\Models\Employee',
    'App\\..\ManageEmployeeProjects'    => 'App\Models\Project',
    'App\\..\OrganizationChart'         => 'App\Models\Employee',
    'App\\..\RolesRelationManager'      => 'App\Models\Role',
    'App\\..\OrganizationResource'      => 'App\Models\Organization',
    'App\\..\DepartmentsRelationManager'=> 'App\Models\Department',
    // ...
])
```

**componentMappings() output:** Returns `[]` — the `ModelTransformer` doesn't introduce new component mappings; it only enriches existing ones with model information.

---

## Enrichment Transformers

### AnnotationTransformer

**Purpose:** Read explicit PHPDoc and PHP attribute annotations from discovered components.

**Input:** `ComponentToResources` (which classes are components), `$result->index` (file metadata for namespace resolution)

**Output:** `ComponentAnnotations` — `array<ComponentFqcn, ExplicitAnnotations>`

**Reads these annotations:**

| Annotation               | Example                        | Stored As                |
|--------------------------|--------------------------------|--------------------------|
| `@filament-model`        | `@filament-model Post`         | `ExplicitAnnotations::$model` |
| `#[FilamentModel]`       | `#[FilamentModel(Post::class)]`| `ExplicitAnnotations::$model` |
| `@filament-page`         | `@filament-page EditPost<Post>`| `ExplicitAnnotations::$pageModels` |
| `@filament-state`        | `@filament-state title`        | `ExplicitAnnotations::$states` |
| `@filament-field`        | `@filament-field email`        | `ExplicitAnnotations::$fields` |

**Example — a shared schema with explicit annotations:**

```php
/**
 * @filament-model Post
 * @filament-state title
 * @filament-state content
 */
class SharedPostForm
{
    public static function configure(Schema $schema): Schema { /* ... */ }
}
```

**Output:**

```php
ComponentAnnotations([
    'App\\..\SharedPostForm' => new ExplicitAnnotations(
        model: 'App\Models\Post',
        pageModels: [],
        states: ['title', 'content'],
        fields: [],
    ),
])
```

Components without annotations are not included in the output.

---

### ComponentContextTransformer

**Purpose:** Synthesize a complete `ComponentNode` for every discovered component, combining all prior transformer outputs.

**Input:** All prior outputs — `ComponentToResources`, `ResourceModels`, `ResourcePages`, `ComponentAnnotations`, `ComponentDeclarations`

**Output:** `ComponentContext` — `array<ComponentFqcn, ComponentNode>`

**This is the final transformer.** Its output is what consumers (rules, type extensions) actually use via `ComponentProvider`.

**Per component, it computes:**

#### Tags

Semantic classification using `ComponentTag` enum:

```php
// Primary types (exactly one)
ComponentTag::Resource          // extends Resource
ComponentTag::Page              // extends any Page class
ComponentTag::RelationManager   // extends RelationManager
ComponentTag::Component         // everything else (schemas, helpers, etc.)

// Page subtypes (added alongside Page)
ComponentTag::EditPage          // extends EditRecord
ComponentTag::CreatePage        // extends CreateRecord
ComponentTag::ListRecords       // extends ListRecords
ComponentTag::ViewRecord        // extends ViewRecord
ComponentTag::ManageRelatedRecords // extends ManageRelatedRecords

// Modifiers (zero or more)
ComponentTag::Nested            // has parentResource set
ComponentTag::Clustered         // has cluster set
ComponentTag::Shared            // owned by multiple resources
```

**Example tags:**

| Component                   | Tags                                          |
|-----------------------------|-----------------------------------------------|
| `EmployeeResource`          | `[Resource]`                                  |
| `EditEmployee`              | `[Page, EditPage]`                            |
| `ManageEmployeeProjects`    | `[Page, ManageRelatedRecords]`                |
| `RolesRelationManager`      | `[RelationManager]`                           |
| `EmployeeForm`              | `[Component]`                                 |
| `DepartmentResource`        | `[Resource, Nested]`                          |
| `BudgetResource`            | `[Resource, Clustered]`                       |

#### Models

- `explicitModel` — from `@filament-model` annotation (highest priority)
- `pageModels` — explicit `@filament-page` annotations, or inferred from resource graph
- `resourceModels` — models for each owning resource

#### Subclass Propagation

Any indexed class extending a known component inherits its parent's resource ownership:

```php
// If CustomEditEmployee extends EditEmployee,
// and EditEmployee is owned by EmployeeResource,
// then CustomEditEmployee is also owned by EmployeeResource.
```

---

## Execution Order

The order matters — later transformers depend on earlier ones:

```
1. PagesTransformer         (reads: index, roots)
2. RelationsTransformer     (reads: index, roots)
3. ModelTransformer          (reads: index, roots, ResourcePages, ResourceRelations)
4. ComponentDiscovery        (reads: all graph transformer componentMappings())
5. PropertyReader            (reads: ComponentToResources)
6. AnnotationTransformer    (reads: ComponentToResources, index)
7. ComponentContextTransformer (reads: everything)
```

`ModelTransformer` must run after `PagesTransformer` and `RelationsTransformer` because it needs their outputs to resolve page and relation manager models.

## Next

- [Data Classes](data-classes.md) — the DTOs that transformers produce and consume
- [Extending](extending.md) — how to add your own transformer