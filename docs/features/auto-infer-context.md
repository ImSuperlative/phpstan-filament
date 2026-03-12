# Auto-infer context

Pre-scans your project for `::configure()` call sites to automatically infer model and page context for shared schema classes.

## Configuration

| Option             | Default | Description                                          |
|--------------------|---------|------------------------------------------------------|
| `autoInferContext`  | `false` | Enable call-site pre-scanning                        |
| `filamentPaths`      | `[]`    | Paths to scan (defaults to PHPStan's analysed paths) |

```neon
parameters:
    PhpstanFilament:
        autoInferContext: true
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

Without this feature, shared schemas need explicit annotations to provide context:

```php
/** @filament-page EditPost<Post> */
class PostForm { /* ... */ }

// Or using attributes:

#[FilamentPage(EditPost::class, model: Post::class)]
class PostForm { /* ... */ }
```

Explicit annotations always take priority over auto-inferred context. See [Annotations](../annotations.md).
