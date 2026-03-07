# Macro method support

Resolves dynamically registered macros on Filament classes so PHPStan recognises them as valid method calls.

## Configuration

| Option         | Default | Description                    |
|----------------|---------|--------------------------------|
| `typeMacroMethods` | `true`  | Enable macro method reflection |

## How it works

Filament components use the `Macroable` trait to allow registering custom methods at runtime. This extension tells PHPStan about those methods by inspecting the registered macro closures:

```php
TextInput::macro('toSlug', function () {
    return $this->dehydrateStateUsing(fn ($state) => Str::slug($state));
});

// PHPStan now recognises this call:
TextInput::make('title')->toSlug();
```

The extension extracts parameter types and return types from the closure, so type-safe usage is enforced.