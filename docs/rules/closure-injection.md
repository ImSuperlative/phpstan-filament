# Closure injection validation

Validates that closure parameter names match Filament's dependency injection system. Reports errors when a parameter name isn't injectable in the current context.

## Configuration

| Option                        | Default | Description                                      |
|-------------------------------|---------|--------------------------------------------------|
| `checkClosureInjection`       | `true`  | Enable closure injection validation              |
| `checkReservedClosureInjection` | `false` | Report reserved name conflicts                 |
| `closureInjectionMethods`     | `[]`    | Override method-specific injection parameters     |

## Invalid parameter names

Untyped parameters must match a known injection name for the component class. Different component types support different parameters:

```php
// ERROR: $get is not available on Column
TextColumn::make('title')
    ->formatStateUsing(fn ($get) => $get('something'));

// ERROR: $old is only available in afterStateUpdated
TextInput::make('title')
    ->visible(fn ($old): bool => $old !== null);

// ERROR: $nonexistent is not a valid injection name
TextInput::make('status')
    ->visible(fn ($nonexistent): bool => true);
```

Valid parameters depend on the component class:

```php
// All valid Component injections
TextInput::make('title')
    ->afterStateUpdated(function ($state, $old, $oldRaw, Get $get, Set $set, $record, $livewire, $model, $operation, $component) {
        // All valid for afterStateUpdated on Component
    });

// All valid Column injections
TextColumn::make('title')
    ->formatStateUsing(fn ($state, $record, $livewire, $rowLoop, $table, $column) => $state);

// Method-specific injections
TextColumn::make('subtitle')
    ->formatStateUsing(fn ($value) => strtoupper($value));    // $value is method-specific

TextColumn::make('duration')
    ->sortable(query: fn ($query, $direction) => ...);         // $query, $direction are method-specific
```

## Type validation

When a parameter has both a name and a type hint, the type must be compatible with the expected injection type:

```php
// ERROR: $state is string|null on TextInput, not int
TextInput::make('title')
    ->afterStateUpdated(function (int $state) {});

// OK: Container-resolved types are always allowed
TextInput::make('body')
    ->visible(fn (\Illuminate\Contracts\Auth\Guard $auth): bool => $auth->check());
```

## Reserved name validation

When `checkReservedClosureInjection: true`, typed parameters whose name matches a known Filament injection name from *any* class — but isn't valid for the *current* context — are reported:

```php
// With reservedClosureInjection: true
// ERROR: $original is a known injection name elsewhere in Filament
ReplicateAction::make('copy')
    ->action(function (Form $original) {});
```

Without this option, typed params with unknown names are silently allowed as container DI.

## Custom injection methods

Override or extend the valid injection parameters for specific methods. This is the same format Filament uses internally — each key is a method name, and the value is the list of parameter names valid for that method's closures:

```neon
parameters:
    filamentPhpstan:
        closureInjectionMethods:
            afterStateUpdated: [old, oldRaw]
            customFilterUsing: [query, search]
            myCustomCallback: [value, record]
```

This tells the rule that `->afterStateUpdated(fn ($old, $oldRaw) => ...)` and `->customFilterUsing(fn ($query, $search) => ...)` are valid.

User-defined methods take priority over built-in ones, so this can also override existing method parameter lists.

## Identifiers

| Identifier                                  | Trigger                         |
|---------------------------------------------|---------------------------------|
| `filamentPhpstan.closureInjection.name`     | Unknown parameter name          |
| `filamentPhpstan.closureInjection.type`     | Incompatible type hint          |
| `filamentPhpstan.closureInjection.reserved` | Reserved name conflict (opt-in) |

## Ignoring rules

```neon
parameters:
    ignoreErrors:
        - identifier: filamentPhpstan.closureInjection.name
```