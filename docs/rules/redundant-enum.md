# Redundant enum detection

Reports when `->enum()` is called on a component that already receives an enum class via `->options()`, since `->options()` calls `->enum()` automatically.

## Configuration

| Option          | Default | Description                     |
|-----------------|---------|---------------------------------|
| `checkRedundantEnum` | `true`  | Enable redundant enum detection |

## How it works

```php
// ERROR: ->enum() is redundant when ->options() receives an enum
Select::make('status')
    ->options(PostStatus::class)
    ->enum(PostStatus::class),

// OK: ->options() only
Select::make('status')
    ->options(PostStatus::class),

// OK: ->enum() only
Select::make('status')
    ->enum(PostStatus::class),

// OK: ->options() with array — ->enum() is needed
Select::make('status')
    ->options(['draft' => 'Draft', 'published' => 'Published'])
    ->enum(PostStatus::class),
```

Applies to components with the `HasOptions` trait: `Select`, `Radio`, `CheckboxList`, `ToggleButtons`.

## Identifier

**Identifier:** `filamentPhpstan.redundantEnum`

**Tip:** "Remove the ->enum() call. When ->options() receives an enum class, it calls ->enum() automatically."

## Ignoring rules

```neon
parameters:
    ignoreErrors:
        - identifier: filamentPhpstan.redundantEnum
```