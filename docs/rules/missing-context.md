# Missing context annotation detection

Reports when a class has inferred page context from the scanner but no explicit `@filament-page` annotation or `#[FilamentPage]` attribute. Adding explicit annotations makes the context unambiguous and avoids relying on inference.

## Configuration

| Option               | Default | Description                              |
|----------------------|---------|------------------------------------------|
| `checkMissingContext` | `true`  | Enable missing annotation detection     |

## How it works

The scanner infers which pages use a component by walking the dependency graph. When a component has inferred pages but no explicit annotation, this rule suggests adding one:

```php
// WARNING: Class PostForm is missing a @filament-page annotation.
// Inferred from: App\Filament\Resources\PostResource\Pages\EditPost
class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title'),
        ]);
    }
}
```

Fix by adding the annotation:

```php
/** @filament-page EditPost */
class PostForm { /* ... */ }

// Or using an attribute:

#[FilamentPage(EditPost::class)]
class PostForm { /* ... */ }
```

The rule provides an auto-fix that adds the `#[FilamentPage]` attribute automatically. Run `phpstan --fix` to apply it.

## When annotations are already present

If explicit annotations cover all inferred pages, no error is reported. Partial coverage still triggers a warning for the missing pages:

```php
// Only EditPost is declared — if CreatePost also uses this schema, it will be reported
/** @filament-page EditPost */
class PostForm { /* ... */ }
```

## Identifier

**Identifier:** `PhpstanFilament.missingContext`

**Tip:** "Add a @filament-page annotation or #[FilamentPage] attribute to make the context explicit."

## Ignoring rules

```neon
parameters:
    ignoreErrors:
        - identifier: PhpstanFilament.missingContext
```

Or disable entirely:

```neon
parameters:
    PhpstanFilament:
        checkMissingContext: false
```
