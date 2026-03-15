# Closure parameter types

Automatically types closure parameters in Filament component methods so PHPStan understands `$record`, `$state`, `$get`, `$set`, `$livewire`, and other injected variables.

## Configuration

| Option         | Default | Description                                      |
|----------------|---------|--------------------------------------------------|
| `typeClosures` | `true`  | Enable all closure parameter type inference      |

## Record typing

When `typeClosures: true`, `$record` resolves to the model class from context. In form components the record is nullable (it may not exist yet during creation). In table columns it is always present:

```php
TextInput::make('title')
    ->default(function ($record) {
        // $record is Post|null (form context — nullable)
    });

TextColumn::make('title')
    ->description(function ($record) {
        // $record is Post (table context — non-null)
    });
```

The `$replica` parameter works the same way as `$record`.

## State typing

`$state` resolves based on the component class:

```php
TextInput::make('title')
    ->afterStateUpdated(function ($state) {
        // $state is string|null
    });

Toggle::make('is_featured')
    ->afterStateUpdated(function ($state) {
        // $state is bool|null
    });

CheckboxList::make('permissions')
    ->afterStateUpdated(function ($state) {
        // $state is array<int, int|string>
    });
```

Component type mappings:

| Component       | `$state` type                    |
|-----------------|----------------------------------|
| `TextInput`     | `string\|null`                   |
| `Toggle`        | `bool\|null`                     |
| `Select`        | `int\|string\|null`              |
| `CheckboxList`  | `array<int, int\|string>`        |
| `KeyValue`      | `array<string, string>\|null`    |
| `Repeater`      | `array\|null`                    |
| `TagsInput`     | `array<int, string>\|null`       |
| `Hidden`        | `mixed`                          |

The `$old` and `$oldRaw` parameters follow the same type as `$state`.

## Operation literal

`$operation` and `$context` resolve to a union of string literals instead of `string`:

```php
TextInput::make('title')
    ->disabled(function ($operation) {
        // $operation is 'create'|'edit'|'view'
    })
    ->visible(function ($context) {
        // $context is 'create'|'edit'|'view'
    });
```

## Injected parameters

Parameters from Filament's dependency injection are typed automatically:

```php
TextInput::make('title')
    ->visible(function ($get, $set, $livewire, $component, $model) {
        // $get is Filament\Schemas\Components\Utilities\Get
        // $set is Filament\Schemas\Components\Utilities\Set
        // $livewire is HasSchemas&Component
        // $component is static(Component)
        // $model is class-string<Model>|null
    });
```

Method-specific parameters are also supported:

```php
TextInput::make('title')
    ->formatStateUsing(fn ($value) => strtoupper($value));    // $value available

TextColumn::make('duration')
    ->sortable(query: fn ($query, $direction) => ...);         // $query, $direction available

TextInput::make('category')
    ->relationship('category', modifyQueryUsing: fn ($query) => $query->active());
```

## Action data

`$data` in action closures resolves to an array shape inferred from the action's `schema()` or `form()`:

```php
Action::make('updateTitle')
    ->schema([
        TextInput::make('title')->required(),
        TextInput::make('slug'),
    ])
    ->action(function (array $data) {
        // $data is array{title: string, slug: string|null}
    });
```

## Action records

`$records` and `$selectedRecords` resolve to a typed collection in bulk actions:

```php
BulkAction::make('publishSelected')
    ->action(function (Collection $records) {
        // $records is Collection<int, Post>
    });
```

## Livewire typing

The `$livewire` parameter resolves through a 3-priority chain:

1. `@filament-page` annotation (explicit override)
2. Schema call-site registry (who calls this schema)
3. Namespace inference (convention-based)

See [Annotations](../annotations.md) for `@filament-page` usage.