# Owner record typing

Types the return value of `getOwnerRecord()` on relation managers and `ManageRelatedRecords` pages so PHPStan knows the parent model class.

## Configuration

| Option        | Default | Description                              |
|---------------|---------|------------------------------------------|
| `typeOwnerRecord` | `true`  | Enable `getOwnerRecord()` type resolution |

## How it works

The extension resolves the model class from the relation manager or page context and returns it as the `getOwnerRecord()` return type:

```php
class CommentsRelationManager extends RelationManager
{
    public function mount(): void
    {
        $post = $this->getOwnerRecord();
        // $post is Post|null
    }
}
```

It also works when calling on a typed variable:

```php
public function handle(CommentsRelationManager $rm): void
{
    $post = $rm->getOwnerRecord();
    // $post is Post|null
}
```

Without this extension, `getOwnerRecord()` returns `Model|null`, losing the specific model type.