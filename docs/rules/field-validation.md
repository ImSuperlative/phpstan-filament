# Field path validation

Validates that strings passed to `::make()` on table columns and infolist entries reference real model attributes, relationships, or accessors.

## Configuration

| Option              | Default | Description                  |
|---------------------|---------|------------------------------|
| `checkFieldPaths` | `1`   | Validation level (0–3)       |

## Global skips

These are always skipped regardless of level:

- **Form fields** — only table columns and infolist entries are validated
- **No model context** — can't determine which model to check against
- **`->records()` tables** — custom data source, not model-backed
- **`->state()` / `->getStateUsing()` fields** — virtual/computed columns

## Validation levels

### Level 0 — Off

No validation.

### Level 1 — Relations + aggregates (default)

Walks nested relations in dot-notation. Stops on first non-relation segment (could be a cast, accessor, or typed property). Aggregates always validate the relation part.

**Plain fields** (`TextColumn::make('title')`)
- Not checked at this level

**Dot-notation** (`TextColumn::make('author.name')`)
- Walks each segment except the leaf
- Valid relationship — continue walking
- Not a relation (method, property, unknown) — stop, no error

```
TextColumn::make('author.name')         // ✓ author() is a relation
TextColumn::make('post.author.name')    // ✓ post() → author() — nested relations
TextColumn::make('writer.name')         // ✓ writer unknown → benefit of the doubt
TextColumn::make('getFullTitle.x')      // ✓ exists but not a relation → stop walking
TextColumn::make('schema.fields')       // ✓ property, not relation → stop walking
```

**Aggregates** (`TextColumn::make('comments_count')`)
- Relation must exist (aggregate format is specific enough to be confident)
- Unknown relation — error
- Column part is not checked

```
TextColumn::make('comments_count')       // ✓ comments() is a relation
TextColumn::make('fakething_count')      // ✗ 'fakething' not a relation
TextColumn::make('comments_avg_rating')  // ✓ relation valid, column not checked
```

### Level 2 — Level 1 + plain fields

Everything from level 1, plus validates that plain field names exist on the model.

**Plain fields** (`TextColumn::make('title')`)
- Must exist as `@property`, `@property-read`, relation method, or any method on the model

```
TextColumn::make('title')           // ✓ @property
TextColumn::make('comments_count')  // ✓ @property-read
TextColumn::make('author')          // ✓ relation method
TextColumn::make('summary')         // ✓ method (new-style accessor)
TextColumn::make('nonexistent')     // ✗ not found on model
```

**Dot-notation** — same as level 1.

**Aggregates** — same as level 1.

### Level 3 — Full path

Everything from level 2, plus full dot-notation path walking with leaf validation and aggregate column validation.

**Plain fields** — same as level 2.

**Dot-notation** (`TextColumn::make('author.name')`)
- Walks the full path, resolving each intermediate segment
- Each segment can be a **relationship** (resolves to the related model) or a **typed property** (resolves to the object type, e.g. Spatie Data objects)
- The leaf is validated on the final resolved class (`@property`, `@property-read`, or method)
- Stops with an error if a segment can't be resolved to an object type (e.g. `string`, `mixed`)

```
TextColumn::make('author.name')                          // ✓ Author has @property name
TextColumn::make('comments.post.author.name')            // ✓ walks Comment → Post → Author
TextColumn::make('options.meta.seo_title')               // ✓ walks PostOptions → PostMeta (typed properties)
TextColumn::make('comments.post.options.meta.seo_title') // ✓ mixed: relations then typed properties
TextColumn::make('author.nonexistent')                   // ✗ 'nonexistent' not on Author
TextColumn::make('options.nonexistent_field')             // ✗ not on PostOptions
TextColumn::make('fakething.name')                       // ✗ 'fakething' not resolvable on model
```

**Aggregates** (`TextColumn::make('comments_avg_rating')`)
- Relation must exist (same as level 1)
- Column is validated against the related model via `@property`

```
TextColumn::make('comments_count')       // ✓
TextColumn::make('fakething_count')      // ✗ 'fakething' not a relation
TextColumn::make('comments_avg_rating')  // ✗ 'rating' doesn't exist on Comment
TextColumn::make('comments_avg_body')    // ✓ 'body' exists on Comment
```

## ManageRelatedRecords / RelationManager

The model is automatically resolved from the `$relationship` property. For example, a `ManageRelatedRecords` page with `$relationship = 'comments'` on a Post resource validates columns against `Comment`, not `Post`.

## Custom queries and `$table->query()`

The extension reads `$table->query(Model::query())` and infers the model from the `Builder` generic type:

```php
// ✓ Model inferred automatically from Builder<Activity>
$table->query(Activity::query())
```

Closures can't be read statically:

```php
// ✗ Can't infer model — closure is opaque
$table->query(fn () => Activity::query()->where(...))
```

Two workarounds:

```php
// Option 1: Type the return of a query method
/** @return Builder<Activity> */
protected function getTableQuery(): Builder
{
    return Activity::query()->where('subject_type', Event::class);
}

// Option 2: Use @filament-model
/** @filament-model Activity */
class ListActivities extends ListRecords { ... }
```

## Custom component validation

When a custom helper class like `CreatedAtEntry::make()` is used inside a resource table, the extension propagates the model context and validates inner `make()` calls automatically:

```php
// CreatedAtEntry::make() used in a Post resource
// → TextEntry::make('created_at') validated against Post
class CreatedAtEntry
{
    public static function make(): TextEntry
    {
        return TextEntry::make('created_at')->since();
    }
}
```

This also works for composite wrappers:

```php
class EmailDeliveryGroup
{
    public static function make(): Group
    {
        return Group::make()->schema([
            TextEntry::make('latestSubmissionEmail.sent_at'),
            TextEntry::make('latestSubmissionEmail.delivered_at'),
        ]);
    }
}
```

No annotations needed — the model context is propagated from the call site.

## Inline ignores

```php
TextColumn::make('custom_field'), // @phpstan-ignore filamentPhpstan.columnName
```

Or disable entirely:

```neon
parameters:
    filamentPhpstan:
        checkFieldPaths: 0
```

## Identifier

**Identifier:** `filamentPhpstan.columnName`

## Ignoring rules

```neon
parameters:
    ignoreErrors:
        - identifier: filamentPhpstan.columnName
```