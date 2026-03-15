# phpstan-filament

A PHPStan extension for [Filament](https://filamentphp.com/) that provides type inference for closures, validation rules, and IDE helper integration.

## Requirements

- PHP 8.4+
- PHPStan 2.0+
- Filament v4 or v5

## Installation

```bash
composer require --dev imsuperlative/phpstan-filament
```

If you have `phpstan/extension-installer`, the extension is registered automatically. Otherwise, add it to your `phpstan.neon`:

```neon
includes:
    - vendor/imsuperlative/phpstan-filament/extension.neon
```

## Configuration

All features are enabled by default. Toggle them in your `phpstan.neon`:

```neon
parameters:
    PhpstanFilament:
        typeClosures: true
        typeOwnerRecord: true
        typeMacroMethods: true
        typeIdeHelper: true
        ideHelperPath: ''
        checkClosureInjection: true
        checkReservedClosureInjection: false
        checkFieldPaths: 1
        checkRelationships: true
        checkRedundantEnum: true
        checkMissingContext: true
        filamentPaths: []
        closureInjectionMethods: []
```

## Features

### [Closure parameter types](docs/features/closure-types.md)

Types `$record`, `$state`, `$get`, `$set`, `$livewire`, `$operation`, `$data`, `$records`, and other injected closure parameters based on component context.

### [Owner record typing](docs/features/owner-record.md)

Types `getOwnerRecord()` on relation managers to return the specific parent model class.

### [Macro method support](docs/features/macro-methods.md)

Resolves dynamically registered macros on Filament classes.

### [IDE helper integration](docs/features/ide-helper.md)

Reads model properties and methods from `_ide_helper_models.php` files.

### [Auto-infer context](docs/features/auto-infer-context.md)

Pre-scans `::configure()` call sites to automatically infer model and page context for shared schema classes.

### [Annotations](docs/annotations.md)

`@filament-model`, `@filament-page`, `@filament-state`, and `@filament-field` — available as PHPDoc tags and PHP 8 attributes for explicit type overrides.

## Rules

### [Closure injection validation](docs/rules/closure-injection.md)

Reports invalid closure parameter names and incompatible type hints.

### [Field path validation](docs/rules/field-validation.md)

Validates `::make()` strings against model attributes and relationships.

### [Relationship validation](docs/rules/relationship-validation.md)

Validates `->relationship()` strings against Eloquent relationships.

### [Redundant enum detection](docs/rules/redundant-enum.md)

Reports unnecessary `->enum()` calls when `->options()` already receives an enum class.

## [Limitations](docs/limitations.md)

Shared/reusable schema classes lose caller context for `$livewire` and `getOwnerRecord()` typing. See the [limitations page](docs/limitations.md) for workarounds.

## License

MIT
