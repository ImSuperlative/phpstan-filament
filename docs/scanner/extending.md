# Extending the Scanner

This guide covers how to add new transformers to the scanner pipeline.

## When to Use Which Type

| Type                    | When to Use                                                         |
|-------------------------|---------------------------------------------------------------------|
| **GraphTransformer**    | You're extracting structural relationships from resource class ASTs   |
| **EnrichmentTransformer** | You need data from discovery/properties to build derived information |

**Rule of thumb:** If your transformer needs `ComponentToResources` or `ComponentDeclarations`, it's an enrichment transformer. If it only needs the file index and roots, it's a graph transformer.

## Adding a Graph Transformer

Graph transformers run early in the pipeline and can influence component discovery. Here's a complete example — suppose you want to extract widget registrations from resources.

### Step 1: Create the Data Class

```php
// src/Data/Scanner/ResourceWidgets.php
namespace ImSuperlative\PhpstanFilament\Data\Scanner;

final class ResourceWidgets
{
    use HasTypedMap;

    /** @param array<string, list<string>> $data resource FQCN => widget FQCNs */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
```

### Step 2: Create the Transformer

```php
// src/Scanner/Transformers/Graph/WidgetsTransformer.php
namespace ImSuperlative\PhpstanFilament\Scanner\Transformers\Graph;

use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceWidgets;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\GraphTransformer;
use ImSuperlative\PhpstanFilament\Support\FileParser;

final class WidgetsTransformer implements GraphTransformer
{
    public function __construct(
        protected FileParser $fileParser,
    ) {}

    public function transform(ProjectScanResult $result): ProjectScanResult
    {
        $widgets = [];

        foreach ($result->roots as $filePath) {
            if (! isset($result->index[$filePath])) {
                continue;
            }

            $record = $result->index[$filePath];
            $parsed = $this->parseGetWidgets($filePath, $record);

            if ($parsed !== []) {
                $widgets[$record->fullyQualifiedName] = $parsed;
            }
        }

        return $result->set(new ResourceWidgets($widgets));
    }

    /** @return array<class-string, list<class-string>> */
    public function componentMappings(ProjectScanResult $result): array
    {
        // Return widgets as components of their resource.
        // This tells ComponentDiscovery to include these classes
        // in the graph walk, so they get tagged with their owning resource.
        return $result->get(ResourceWidgets::class)?->all() ?? [];
    }

    /** @return list<string> */
    protected function parseGetWidgets(string $filePath, $record): array
    {
        // Parse the getWidgets() method and extract widget class references.
        // Follow the same pattern as PagesTransformer or RelationsTransformer.
        // ...
    }
}
```

### Step 3: Register in `config/scanner.neon`

```neon
services:
    -
        class: ImSuperlative\PhpstanFilament\Scanner\Transformers\Graph\WidgetsTransformer
        tags:
            - phpstan.filament.graphTransformer
```

That's it. The `tagged(phpstan.filament.graphTransformer)` injection in `FilamentProjectScanner` and `ComponentDiscovery` picks it up automatically.

### What Happens

1. `FilamentProjectScanner::scan()` calls `->through($this->graphTransformers)` — your transformer runs alongside Pages, Relations, and Model transformers
2. Your `transform()` writes `ResourceWidgets` to the result
3. `ComponentDiscovery` calls your `componentMappings()` to get starting points for the graph walk
4. Widget classes get discovered and tagged with their owning resources
5. Later transformers and rules can access `ResourceWidgets` via `$result->get(ResourceWidgets::class)`

## Adding an Enrichment Transformer

Enrichment transformers run after discovery and have access to everything. Here's an example — computing which components have custom validation rules.

### Step 1: Create the Data Class

```php
// src/Data/Scanner/ComponentValidation.php
namespace ImSuperlative\PhpstanFilament\Data\Scanner;

final class ComponentValidation
{
    use HasTypedMap;

    /** @param array<string, list<string>> $data component FQCN => validation rule classes */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
```

### Step 2: Create the Transformer

```php
// src/Scanner/Transformers/Enrichment/ValidationTransformer.php
namespace ImSuperlative\PhpstanFilament\Scanner\Transformers\Enrichment;

use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentToResources;
use ImSuperlative\PhpstanFilament\Data\Scanner\ComponentValidation;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Scanner\Transformers\EnrichmentTransformer;

final class ValidationTransformer implements EnrichmentTransformer
{
    public function transform(ProjectScanResult $result): ProjectScanResult
    {
        $componentToResources = $result->find(ComponentToResources::class);
        if ($componentToResources === null) {
            return $result->set(new ComponentValidation([]));
        }

        $validation = [];

        foreach ($componentToResources->all() as $componentClass => $resourceClasses) {
            $rules = $this->findValidationRules($componentClass);
            if ($rules !== []) {
                $validation[$componentClass] = $rules;
            }
        }

        return $result->set(new ComponentValidation($validation));
    }

    /** @return list<string> */
    protected function findValidationRules(string $componentClass): array
    {
        // Your logic here — inspect AST, reflection, etc.
        // You have access to all discovered components via ComponentToResources.
        // ...
    }
}
```

### Step 3: Register in `config/scanner.neon`

```neon
services:
    -
        class: ImSuperlative\PhpstanFilament\Scanner\Transformers\Enrichment\ValidationTransformer
        tags:
            - phpstan.filament.enrichmentTransformer
```

### Consuming from Rules

```php
class MyCustomRule implements Rule
{
    public function __construct(
        protected FilamentProjectIndex $index,
    ) {}

    public function processNode(Node $node, Scope $scope): array
    {
        $validation = $this->index->get(ComponentValidation::class);
        $rules = $validation?->get($scope->getClassReflection()->getName());
        // ...
    }
}
```

## Key Patterns

### Always Handle Missing Data Gracefully

Other transformers' outputs may not exist (if they haven't run, or if the project has no matching components):

```php
// Use find() (nullable) instead of get() (throws) when the data might not exist
$pages = $result->find(ResourcePages::class);
if ($pages === null) {
    return $result->set(new MyOutput([]));
}
```

### Use FileParser for AST Work

Inject `FileParser` for consistent PHP parsing:

```php
public function __construct(
    protected FileParser $fileParser,
) {}

// Parse a file
$stmts = $this->fileParser->parseFile($filePath);

// Get a NodeFinder instance
$finder = $this->fileParser->nodeFinder();
```

### Use NamespaceHelper for Name Resolution

When resolving class names from AST nodes, use the file's `useMap` and `namespace`:

```php
use ImSuperlative\PhpstanFilament\Support\NamespaceHelper;

$fqcn = NamespaceHelper::toFullyQualified(
    (string) $node->class,   // Short name from AST
    $record->useMap,          // Import map from FileMetadata
    $record->namespace,       // Namespace from FileMetadata
);
```

### Transformer Dependencies

If your transformer depends on another transformer's output, ensure the execution order is correct:

- **Graph transformers** run in the order they're registered in `scanner.neon`
- **Enrichment transformers** run in the order they're registered in `scanner.neon`
- If order matters, register your transformer after its dependencies in the neon file

### Testing

Scanner transformers can be tested in isolation by constructing a `ProjectScanResult` manually:

```php
it('extracts widgets from resource', function () {
    $result = new ProjectScanResult(
        index: [
            '/path/to/EmployeeResource.php' => new FileMetadata(
                fullyQualifiedName: 'App\\EmployeeResource',
                extends: 'Filament\\Resources\\Resource',
                traits: [],
                useMap: ['StatsWidget' => 'App\\Widgets\\StatsWidget'],
                namespace: 'App',
                isTrait: false,
            ),
        ],
        roots: ['/path/to/EmployeeResource.php'],
    );

    $transformer = new WidgetsTransformer(new FileParser);
    $result = $transformer->transform($result);

    $widgets = $result->get(ResourceWidgets::class);
    expect($widgets->get('App\\EmployeeResource'))->toBe([
        'App\\Widgets\\StatsWidget',
    ]);
});
```

## Checklist

When adding a new transformer:

- [ ] Create the data class in `src/Data/Scanner/` using `HasTypedMap`
- [ ] Create the transformer in the appropriate subdirectory:
  - `src/Scanner/Transformers/Graph/` for graph transformers
  - `src/Scanner/Transformers/Enrichment/` for enrichment transformers
- [ ] Implement the correct interface (`GraphTransformer` or `EnrichmentTransformer`)
- [ ] Register the service in `config/scanner.neon` with the correct tag
- [ ] If graph transformer: implement `componentMappings()` (return `[]` if no new components)
- [ ] Handle missing upstream data with `find()` (nullable) not `get()` (throws)
- [ ] Add tests in `tests/Unit/Scanner/`