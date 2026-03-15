# Scanner Pipeline

The scanner runs as a single pipeline orchestrated by `FilamentProjectScanner`. It executes once at PHPStan boot time and caches the result in `FilamentProjectIndex`.

## Entry Points

### `FilamentProjectScanner`

The orchestrator. Chains all phases together using `ScanPipeline`:

```php
// src/Scanner/FilamentProjectScanner.php

public function scan(): ProjectScanResult
{
    return ScanPipeline::send($this->projectIndexer->index())
        ->through($this->graphTransformers)
        ->then(fn (ProjectScanResult $r) => $this->componentDiscovery->discover($r))
        ->then(fn (ProjectScanResult $r) => $this->propertyReader->read($r))
        ->through($this->enrichmentTransformers)
        ->thenReturn();
}
```

### `FilamentProjectIndex`

The lazy facade that consumers use. Wraps the scanner with memoization:

```php
// src/Scanner/FilamentProjectIndex.php

protected ?ProjectScanResult $scanResult = null;

protected function scanResult(): ProjectScanResult
{
    return $this->scanResult ??= $this->scanner->scan();
}
```

The first call to any `FilamentProjectIndex` method triggers the full scan. Subsequent calls use the cached result.

### `ScanPipeline`

A minimal fluent pipeline that passes `ProjectScanResult` through transformers and closures:

```php
ScanPipeline::send($initial)          // Start with a ProjectScanResult
    ->through([$transformer1, ...])   // Run ScanTransformer::transform() on each
    ->then(fn ($r) => $customStep($r))  // Run arbitrary closure
    ->thenReturn();                   // Return final ProjectScanResult
```

`through()` calls each transformer's `transform()` method sequentially. `then()` runs a closure. Both pass forward the (potentially mutated) `ProjectScanResult`.

## Phase 1: Indexing

**Class:** `ProjectIndexer`
**Output:** `ProjectScanResult` with `$index` and `$roots`

The indexer discovers all Filament-related PHP files and extracts lightweight metadata from each.

### File Discovery

1. Collects PHP files from configured paths (`filamentPaths`, `analysedPaths`, `analysedPathsFromConfig`)
2. Pre-filters with `str_contains($code, 'use Filament\\')` — this is critical for performance, skipping files that don't import Filament (avoids parsing thousands of irrelevant files)
3. Parses each file for class/trait metadata using PHP-Parser

### Metadata Extraction

For each discovered file, the indexer builds a `FileMetadata`:

```php
new FileMetadata(
    fullyQualifiedName: 'App\\Filament\\Resources\\Employees\\EmployeeResource',
    extends: 'Filament\\Resources\\Resource',
    traits: [],
    useMap: [
        'Resource' => 'Filament\\Resources\\Resource',
        'Employee' => 'App\\Models\\Employee',
        // ...
    ],
    namespace: 'App\\Filament\\Resources\\Employees',
    isTrait: false,
);
```

### Root Identification

A file is a "root" if its `extends` value is one of:

- `Filament\Resources\Resource`
- `Filament\Resources\Pages\ManageRelatedRecords`

These are the starting points for the graph walk. In the example project, roots would be:

- `EmployeeResource` (extends Resource)
- `OrganizationResource` (extends Resource)
- `ProjectResource` (extends Resource)
- `DepartmentResource` (extends Resource)
- `LocationResource` (extends Resource)
- `DeskResource` (extends Resource)
- `BudgetResource` (extends Resource)
- `ExpenseResource` (extends Resource)
- `ManageEmployeeProjects` (extends ManageRelatedRecords)

## Phase 2: Graph Transformers

**Classes:** `PagesTransformer`, `RelationsTransformer`, `ModelTransformer`
**Tag:** `phpstan.filament.graphTransformer`

Graph transformers extract structural relationships from resource classes. They run sequentially via `->through($this->graphTransformers)`.

Each graph transformer:
1. Receives the current `ProjectScanResult`
2. Reads from `$result->index` and `$result->roots`
3. Stores its output via `$result->set(new SomeDataClass(...))`
4. Also provides `componentMappings()` — direct resource-to-component links for `ComponentDiscovery`

See [Transformers](transformers.md) for details on each.

### After graph transformers, the result contains:

| Attribute         | Type                                        | Source              |
|-------------------|---------------------------------------------|---------------------|
| `ResourcePages`    | `array<ResourceFqcn, array<slug, PageFqcn>>` | PagesTransformer    |
| `ResourceRelations`| `array<ResourceFqcn, list<ManagerFqcn>>`     | RelationsTransformer|
| `ResourceModels`   | `array<ComponentFqcn, ModelFqcn>`            | ModelTransformer    |

## Phase 3: Component Discovery

**Class:** `ComponentDiscovery`
**Output:** `ComponentToResources` + `DependencyGraph`

This is where the scanner figures out which components belong to which resources by walking the class dependency graph.

### Step 1: Collect Starting Points

Starting points = roots + all classes returned by transformer `componentMappings()`:

```
Roots:                  EmployeeResource, OrganizationResource, ProjectResource, ...
From PagesTransformer:  ListEmployees, EditEmployee, CreateEmployee, ...
From RelationsTransformer: RolesRelationManager, DepartmentsRelationManager, ...
```

### Step 2: Build Dependency Graph (BFS)

Starting from each starting point, the scanner reads each file's AST looking for:

- **Static calls** to `::configure()` and `::make()` on project classes (not Filament base classes)
- **Extends** relationships (parent classes within the project)
- **Trait usage** (traits within the project)

For example, if `EmployeeResource` has:

```php
public function form(Schema $schema): Schema
{
    return EmployeeForm::configure($schema);
}
```

The graph records: `EmployeeResource → EmployeeForm`.

This produces an adjacency list like:

```php
[
    'EmployeeResource' => ['EmployeeForm', 'EmployeesTable', 'EmployeeInfolist'],
    'EditEmployee' => ['EmployeeForm'],
    'ViewEmployee' => ['EmployeeInfolist'],
    'ListEmployees' => ['EmployeesTable'],
    // ...
]
```

### Step 3: Tag Reachable Classes (DFS)

For each root, the scanner walks the dependency graph depth-first and tags every reachable class with that root as its owning resource:

```php
// Starting from EmployeeResource:
'EmployeeForm'     => ['EmployeeResource']
'EmployeesTable'   => ['EmployeeResource']
'EmployeeInfolist' => ['EmployeeResource']
```

If a component is reachable from multiple roots, it gets tagged with all of them (this marks it as "shared").

### Step 4: Register Roots + Apply Direct Mappings

- Resource roots are registered as their own components (`EmployeeResource → [EmployeeResource]`)
- Direct mappings from transformers are applied (pages and relation managers get their parent resource)

## Phase 4: Property Reading

**Class:** `PropertyReader`
**Output:** `ComponentDeclarations`

For each discovered component, the `PropertyReader` reads class properties by walking up the hierarchy:

| Property          | Getter             | Example Value                          |
|-------------------|--------------------|----------------------------------------|
| `model`           | `getModel()`       | `App\Models\Employee`                  |
| `resource`        | `getResource()`    | `App\Filament\Resources\EmployeeResource` |
| `relatedResource` | `getRelatedResource()` | `App\Filament\Resources\ProjectResource` |
| `relationship`    | `getRelationship()`| `projects`                             |
| `cluster`         | `getCluster()`     | `App\Filament\Clusters\FinanceCluster` |
| `parentResource`  | `getParentResource()` | `App\Filament\Resources\OrganizationResource` |

**Resolution priority for model:**
1. `@filament-model` annotation or `@filament-page<Model>` annotation (highest)
2. Static property declared on the class
3. Getter method return type

**Hierarchy walking** stops at Filament base classes (`Resource`, `RelationManager`, `Page`) to avoid picking up generic defaults.

## Phase 5: Enrichment Transformers

**Classes:** `AnnotationTransformer`, `ComponentContextTransformer`
**Tag:** `phpstan.filament.enrichmentTransformer`

Enrichment transformers run after discovery and property reading. They have access to all previous phase outputs.

### AnnotationTransformer

Reads PHPDoc tags and PHP attributes from each discovered component:

- `@filament-model Post` → explicit model override
- `@filament-page EditPost<Post>` → page-to-model mapping
- `@filament-state title` → field name declarations
- `@filament-field email` → field name declarations

### ComponentContextTransformer

The final synthesizer. It combines all prior outputs into a single `ComponentContext` map — one `ComponentNode` per component with:

- **Tags** — semantic classification (Resource, Page, EditPage, Nested, Clustered, Shared, etc.)
- **Models** — explicit and inferred models
- **Pages** — which pages belong to each resource
- **Resources** — which resources own this component
- **Declaration** — parsed class properties

It also propagates context to subclasses: any indexed class extending a known component inherits its resources.

### After enrichment, the final `ProjectScanResult` contains:

| Attribute              | Phase Added          |
|------------------------|----------------------|
| `ResourcePages`         | Graph (Phase 2)      |
| `ResourceRelations`     | Graph (Phase 2)      |
| `ResourceModels`        | Graph (Phase 2)      |
| `DependencyGraph`       | Discovery (Phase 3)  |
| `ComponentToResources`  | Discovery (Phase 3)  |
| `ComponentDeclarations` | Property (Phase 4)   |
| `ComponentAnnotations`  | Enrichment (Phase 5) |
| `ComponentContext`      | Enrichment (Phase 5) |

## Complete Example

Given `EmployeeResource` from the example project, the final `ComponentNode` for `EditEmployee` would look like:

```php
new ComponentNode(
    tags: [ComponentTag::Page, ComponentTag::EditPage],
    explicitModel: null,
    pageModels: [
        'App\\..\\EditEmployee' => 'App\\Models\\Employee',
        'App\\..\\CreateEmployee' => 'App\\Models\\Employee',
        'App\\..\\ListEmployees' => 'App\\Models\\Employee',
        'App\\..\\ViewEmployee' => 'App\\Models\\Employee',
        'App\\..\\ManageEmployeeProjects' => 'App\\Models\\Project',
        // ...
    ],
    resourceModels: [
        'App\\..\\EmployeeResource' => 'App\\Models\\Employee',
    ],
    resourcePages: [
        'App\\..\\EmployeeResource' => [
            'App\\..\\ListEmployees',
            'App\\..\\CreateEmployee',
            'App\\..\\EditEmployee',
            'App\\..\\ViewEmployee',
            'App\\..\\ManageEmployeeProjects',
            'App\\..\\OrganizationChart',
        ],
    ],
    owningResources: ['App\\..\\EmployeeResource'],
    declaration: new ComponentDeclaration(
        resourceClass: 'App\\..\\EmployeeResource',
    ),
);
```

And `EmployeeForm` (a shared schema class referenced via `EmployeeForm::configure($schema)`) would have:

```php
new ComponentNode(
    tags: [ComponentTag::Component],
    explicitModel: null,
    pageModels: [/* same as above, inherited from resource */],
    resourceModels: [
        'App\\..\\EmployeeResource' => 'App\\Models\\Employee',
    ],
    resourcePages: [/* same as above */],
    owningResources: ['App\\..\\EmployeeResource'],
    declaration: new ComponentDeclaration(),
);
```

## Next

- [Transformers](transformers.md) — deep dive into each transformer
- [Data Classes](data-classes.md) — all DTOs and access patterns