# Annotations

Four annotations provide explicit type hints when automatic resolution isn't sufficient. Each is available as both a PHPDoc tag and a PHP 8 attribute. Short class names are resolved using the file's `use` statements — no need for fully qualified names if you have a `use` import.

All attributes are repeatable and can be placed on classes or methods.

## `@filament-model` / `#[FilamentModel]`

Declares the model class for a schema or resource class. Used by `$record` typing, field validation, and relationship validation.

PHPDoc:

```php
/**
 * @filament-model App\Models\Post
 */
class PostFormSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->default(function ($record) {
                    // $record is Post|null
                }),
        ]);
    }
}
```

Attribute:

```php
use ImSuperlative\FilamentPhpstan\Attributes\FilamentModel;

#[FilamentModel(Post::class)]
class PostFormSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->default(function ($record) {
                    // $record is Post|null
                }),
        ]);
    }
}
```

### Custom queries

When a page uses a custom query targeting a different model (e.g. a comments page on a Post resource), use `@filament-model` to specify the correct model:

```php
use App\Models\Comment;

/** @filament-model Comment */
class ListComments extends ViewRecord implements HasTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(Comment::query())
            ->columns([
                TextColumn::make('post.title'),  // validated against Comment
            ]);
    }
}
```

Alternatively, if your query method declares a generic return type, the model is inferred automatically:

```php
/** @return Builder<Comment> */
protected function getTableQuery(): Builder
{
    return Comment::query()->where('is_approved', true);
}
```

Without either approach, standalone classes or custom query pages have no model context, and validation is skipped.

## `@filament-page` / `#[FilamentPage]`

Overrides the Livewire component type for `$livewire` resolution. Supports union types and multiple tags.

PHPDoc:

```php
/**
 * @filament-page EditPost
 */
class PostFormSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->visible(function ($livewire) {
                    // $livewire is EditPost
                }),
        ]);
    }
}
```

Attribute:

```php
use ImSuperlative\FilamentPhpstan\Attributes\FilamentPage;

#[FilamentPage(EditPost::class)]
class PostFormSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->visible(function ($livewire) {
                    // $livewire is EditPost
                }),
        ]);
    }
}
```

The attribute also accepts a `model` parameter to specify the model directly:

```php
#[FilamentPage(EditPost::class, model: Post::class)]
```

Union syntax and multiple tags/attributes are equivalent:

```php
/** @filament-page EditPost|CreatePost */

// Same as:

/**
 * @filament-page EditPost
 * @filament-page CreatePost
 */

// Same as:

#[FilamentPage(EditPost::class)]
#[FilamentPage(CreatePost::class)]

// Or using an array:

#[FilamentPage([EditPost::class, CreatePost::class])]
```

All result in `$livewire` typed as `EditPost|CreatePost`.

## `@filament-state` / `#[FilamentState]`

Overrides the inferred `$state` type for closure parameters. The trailing field name is optional — without it, the override applies to all fields in the class.

PHPDoc:

```php
/**
 * @filament-state Carbon\CarbonInterface updated_at
 * @filament-state Carbon\CarbonInterface created_at
 */
class PostFormSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('updated_at')
                ->afterStateUpdated(function ($state) {
                    // $state is CarbonInterface (instead of default string|null)
                }),
        ]);
    }
}
```

Attribute:

```php
use ImSuperlative\FilamentPhpstan\Attributes\FilamentState;

#[FilamentState(CarbonInterface::class, field: 'updated_at')]
#[FilamentState(CarbonInterface::class, field: 'created_at')]
class PostFormSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('updated_at')
                ->afterStateUpdated(function ($state) {
                    // $state is CarbonInterface (instead of default string|null)
                }),
        ]);
    }
}
```

A global override (no field name) applies to all fields:

```php
/**
 * @filament-state string updated_at
 * @filament-state int
 */
class MyResource extends Resource
{
    // $state is 'string' for updated_at closures
    // $state is 'int' for all other closures
}

// Same with attributes:

#[FilamentState('string', field: 'updated_at')]
#[FilamentState('int')]
class MyResource extends Resource { /* ... */ }
```

## `@filament-field` / `#[FilamentField]`

Overrides the type resolution of a dot-notation segment in [field path validation](rules/field-validation.md). Useful when the extension can't resolve a segment type (e.g. `morphTo` relationships that resolve to base `Model`).

PHPDoc:

```php
use App\Models\Post;

/**
 * @filament-model Comment
 * @filament-field Post commentable
 */
class ListComments extends ViewRecord implements HasTable
{
    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('commentable.title'),  // 'commentable' resolves to Post, 'title' validated against Post
        ]);
    }
}
```

Attribute:

```php
use ImSuperlative\FilamentPhpstan\Attributes\FilamentField;
use ImSuperlative\FilamentPhpstan\Attributes\FilamentModel;

#[FilamentModel(Comment::class)]
#[FilamentField(Post::class, field: 'commentable')]
class ListComments extends ViewRecord implements HasTable
{
    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('commentable.title'),  // 'commentable' resolves to Post, 'title' validated against Post
        ]);
    }
}
```

When a dot-path segment resolves to `Illuminate\Database\Eloquent\Model` (e.g. from a `morphTo` relationship like `commentable`), validation is automatically skipped since the concrete type can't be determined. Use `@filament-field` or `#[FilamentField]` to provide the concrete type and enable full validation.