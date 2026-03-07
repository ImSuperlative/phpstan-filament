# PHPDoc annotations

Four PHPDoc annotations provide explicit type hints when automatic resolution isn't sufficient. Short class names are resolved using the file's `use` statements — no need for fully qualified names if you have a `use` import.

## `@filament-model`

Declares the model class for a schema or resource class. Used by `$record` typing, field validation, and relationship validation:

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

### Custom queries

When a page uses a custom query targeting a different model (e.g. an Activity log page on an Event resource), use `@filament-model` to specify the correct model:

```php
use Spatie\Activitylog\Models\Activity;

/** @filament-model Activity */
class ListActivities extends ViewRecord implements HasTable
{
    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query())
            ->columns([
                TextColumn::make('causer.name'),  // validated against Activity
            ]);
    }
}
```

Alternatively, if your query method declares a generic return type, the model is inferred automatically:

```php
/** @return Builder<Activity> */
protected function getTableQuery(): Builder
{
    return Activity::query()->where('subject_type', Event::class);
}
```

Without either approach, standalone classes or custom query pages have no model context, and validation is skipped.

## `@filament-page`

Overrides the Livewire component type for `$livewire` resolution. Supports union types and multiple tags:

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

Union syntax and multiple tags are equivalent:

```php
/** @filament-page EditPost|CreatePost */

// Same as:

/**
 * @filament-page EditPost
 * @filament-page CreatePost
 */
```

Both result in `$livewire` typed as `EditPost|CreatePost`.

## `@filament-state`

Overrides the inferred `$state` type for closure parameters. The trailing field name is optional — without it, the override applies to all fields in the class:

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
```

## `@filament-field`

Overrides the type resolution of a dot-notation segment in [field path validation](rules/field-validation.md). Useful when the extension can't resolve a segment type (e.g. `morphTo` relationships that resolve to base `Model`):

```php
use App\Models\User;

/**
 * @filament-model Activity
 * @filament-field User causer
 */
class ListActivities extends ViewRecord implements HasTable
{
    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('causer.name'),  // 'causer' resolves to User, 'name' validated against User
        ]);
    }
}
```

When a dot-path segment resolves to `Illuminate\Database\Eloquent\Model` (e.g. from a `morphTo` relationship), validation is automatically skipped since the concrete type can't be determined. Use `@filament-field` to provide the concrete type and enable full validation.