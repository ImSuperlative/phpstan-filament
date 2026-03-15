# Scanner Overview

The scanner builds a complete picture of your Filament project at PHPStan boot time. It discovers resources, pages, relation managers, models, and shared components — then makes that information available to rules and type extensions.

## Why It Exists

Filament's architecture is convention-based: resources declare pages in `getPages()`, models in `$model`, relations in `getRelations()`. PHPStan rules need this structural knowledge to validate closures, infer types, and check field paths. The scanner extracts it once and caches the result for the entire analysis run.

## Pipeline at a Glance

```
ProjectIndexer::index()
    │  Discovers files with `use Filament\` imports
    │  Parses each into FileMetadata (FQCN, parent, traits, imports)
    │  Identifies resource roots (classes extending Resource)
    ▼
┌─────────────────────────────────────────────┐
│  GRAPH TRANSFORMERS (structure extraction)   │
│                                              │
│  1. PagesTransformer    → ResourcePages      │
│  2. RelationsTransformer → ResourceRelations │
│  3. ModelTransformer    → ResourceModels     │
└──────────────────────────┬──────────────────-┘
                           ▼
ComponentDiscovery::discover()
    │  BFS: builds class dependency graph from static calls
    │  DFS: tags reachable classes with owning resources
    │  Outputs: ComponentToResources + DependencyGraph
    ▼
PropertyReader::read()
    │  Reads $model, $resource, $relationship, etc.
    │  Walks class hierarchy, stops at Filament base classes
    │  Outputs: ComponentDeclarations
    ▼
┌──────────────────────────────────────────────┐
│  ENRICHMENT TRANSFORMERS (context synthesis)  │
│                                               │
│  1. AnnotationTransformer → ComponentAnnotations │
│  2. ComponentContextTransformer → ComponentContext │
└──────────────────────────┬───────────────────-┘
                           ▼
              ProjectScanResult (complete)
```

## Quick Reference

| I want to...                          | Use                                                      |
|---------------------------------------|----------------------------------------------------------|
| Get the model for a component         | `$index->getComponent($class)?->getModel()`              |
| Get pages for a resource              | `$index->getPagesForResource($resourceClass)`            |
| Get relation managers for a resource  | `$index->getRelationsForResource($resourceClass)`        |
| Check which resources own a component | `$index->getResourcesForComponent($componentClass)`      |
| Get model from PHPStan scope          | `$index->resolveModelFromScope($scope)`                  |
| Check if a component is nested        | `$index->getComponent($class)?->isNested()`              |
| Check component tags                  | `$index->getComponent($class)?->hasTag(ComponentTag::X)` |
| Access raw scan data                  | `$index->get(ResourceModels::class)`                     |

All methods are on `FilamentProjectIndex`, which is the main entry point injected via PHPStan's DI container.

## Example Project

Throughout these docs, examples reference an HR management application with this structure:

```
Resources/
├── Employees/
│   ├── EmployeeResource.php          (model: Employee)
│   ├── Pages/
│   │   ├── ListEmployees.php
│   │   ├── CreateEmployee.php
│   │   ├── EditEmployee.php
│   │   ├── ViewEmployee.php
│   │   ├── ManageEmployeeProjects.php  (ManageRelatedRecords, model: Project)
│   │   └── OrganizationChart.php       (custom page)
│   ├── RelationManagers/
│   │   └── RolesRelationManager.php    (relationship: roles)
│   └── Schemas/
│       └── EmployeeForm.php            (shared schema)
│
├── Organizations/
│   ├── OrganizationResource.php      (model: Organization)
│   ├── RelationManagers/
│   │   ├── DepartmentsRelationManager.php
│   │   └── LocationsRelationManager.php
│   └── Resources/                     (nested resources)
│       ├── Departments/
│       │   └── DepartmentResource.php  (parentResource: OrganizationResource)
│       └── Locations/
│           └── LocationResource.php    (parentResource: OrganizationResource)
│               └── Resources/
│                   └── Desks/
│                       └── DeskResource.php  (parentResource: LocationResource)
│
├── Projects/
│   └── ProjectResource.php           (model: Project)
│
└── Clusters/Finance/
    ├── FinanceCluster.php
    └── Resources/
        ├── Budgets/BudgetResource.php    (cluster: FinanceCluster)
        └── Expenses/ExpenseResource.php  (cluster: FinanceCluster)
```

## File Structure

```
src/Scanner/
├── FilamentProjectScanner.php          # Orchestrates the pipeline
├── FilamentProjectIndex.php            # Lazy facade — main consumer API
├── ProjectScanResult.php               # Typed attribute bag
├── ScanPipeline.php                    # Fluent pipeline helper
├── Indexing/
│   ├── ProjectIndexer.php              # File discovery + metadata parsing
│   ├── ComponentDiscovery.php          # BFS/DFS graph walk
│   └── PropertyReader.php              # Class property/method reading
└── Transformers/
    ├── ScanTransformer.php             # Base interface
    ├── GraphTransformer.php            # Interface for structure transformers
    ├── EnrichmentTransformer.php       # Interface for enrichment transformers
    ├── Graph/
    │   ├── PagesTransformer.php
    │   ├── RelationsTransformer.php
    │   ├── ModelTransformer.php
    │   └── Model/
    │       └── ParsesModelFromClass.php
    └── Enrichment/
        ├── AnnotationTransformer.php
        └── ComponentContextTransformer.php

src/Data/Scanner/
├── HasTypedMap.php                     # Generic typed map trait
├── ComponentNode.php                   # Final context per component
├── ComponentTag.php                    # Semantic tag enum
├── ComponentDeclaration.php            # Parsed class properties
├── ComponentDeclarations.php           # Map of declarations
├── ComponentToResources.php            # Component → resource mapping
├── ComponentAnnotations.php            # Explicit PHPDoc/attribute annotations
├── ComponentContext.php                # Final synthesized context map
├── ExplicitAnnotations.php             # Parsed annotation values
├── ResourcePages.php                   # Resource → pages mapping
├── ResourceRelations.php               # Resource → relation managers mapping
├── ResourceModels.php                  # Resource/page/manager → model mapping
└── DependencyGraph.php                 # Class dependency adjacency list
```

## Next

- [Pipeline](pipeline.md) — detailed walkthrough of each phase
- [Transformers](transformers.md) — what each transformer does and outputs
- [Data Classes](data-classes.md) — all scanner DTOs and how to access them
- [Extending](extending.md) — how to add new transformers