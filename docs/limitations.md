# Limitations

## Shared schema classes and type context

When schemas are extracted into shared/reusable classes, the extension loses context about which Livewire page or resource is calling it. This affects features that depend on the caller's context.

### `getOwnerRecord()` in shared schemas

When a shared schema class is used from a `ManageRelatedRecords` page, closures inside the schema can't resolve `getOwnerRecord()` to the specific model type:

```php
class ManageScannerAuth extends ManageRelatedRecords
{
    protected static string $resource = EventResource::class;
    protected static string $relationship = 'scannerAuths';

    public function form(Schema $schema): Schema
    {
        return ScannerAuthForm::configure($schema);
    }
}

class ScannerAuthForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('occasion_type')
                ->default(fn (Page $livewire) => $livewire instanceof ManageRelatedRecords
                    ? $livewire->getOwnerRecord()->className  // returns Model, not Event
                    : null),
        ]);
    }
}
```

`getOwnerRecord()` resolves to `Model` because the extension runs in `ScannerAuthForm`'s scope, which has no resource context. PHPStan doesn't have a mechanism for cross-class context propagation.

### Workaround

Use `@var` inside the closure when a specific type is needed:

```php
->default(function (Page $livewire) {
    if (! $livewire instanceof ManageRelatedRecords) {
        return null;
    }

    /** @var Event $owner */
    $owner = $livewire->getOwnerRecord();
    return $owner->className;
})
```

This is an inherent tradeoff: extracting schemas into shared classes gains reusability but loses the resource-specific type context that the extension relies on.