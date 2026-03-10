# Relationship validation

Validates that strings passed to `->relationship()` calls correspond to real Eloquent relationships on the model.

## Configuration

| Option         | Default | Description                       |
|----------------|---------|-----------------------------------|
| `checkRelationships` | `true`  | Enable relationship name validation |

## How it works

```php
// ERROR: 'writer' is not a relationship on Post
Select::make('author_id')
    ->relationship('writer', 'name'),

// ERROR: 'categorie' is not a relationship on Post (typo)
Select::make('category_id')
    ->relationship('categorie', 'name'),

// OK: 'author' is a BelongsTo on Post
Select::make('author_id')
    ->relationship('author', 'name'),
```

## Identifier

**Identifier:** `PhpstanFilament.relationship`

## Ignoring rules

```neon
parameters:
    ignoreErrors:
        - identifier: PhpstanFilament.relationship
```
