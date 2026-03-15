# Auto-infer context

The scanner automatically pre-scans your project for `::configure()` and `::make()` call sites to infer model and page context for shared schema classes. This is always enabled.

## Configuration

| Option          | Default | Description                                          |
|-----------------|---------|------------------------------------------------------|
| `filamentPaths` | `[]`    | Paths to scan (defaults to PHPStan's analysed paths) |

```neon
parameters:
    PhpstanFilament:
        filamentPaths:
            - app/Filament
```

## Example

```php
// EditPost calls PostForm::configure() — context is inferred automatically
class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->visible(function ($record) {
                    // $record is Post|null
                }),
        ]);
    }
}
```

If the scanner cannot determine context (e.g. the schema is used outside the scanned paths), use explicit annotations:

```php
/** @filament-page EditPost<Post> */
class PostForm { /* ... */ }

// Or using attributes:

#[FilamentPage(EditPost::class, model: Post::class)]
class PostForm { /* ... */ }
```

Explicit annotations always take priority over auto-inferred context. See [Annotations](../annotations.md).