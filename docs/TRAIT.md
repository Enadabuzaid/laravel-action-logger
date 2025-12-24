# HasActionLogs Trait

The `HasActionLogs` trait provides automatic action logging for Eloquent models.

## Basic Usage

Simply add the trait to any model:

```php
use Enadstack\ActionLogger\Traits\HasActionLogs;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasActionLogs;
}
```

Now all CRUD operations on this model will be automatically logged!

## Automatic Event Logging

The trait automatically logs these Eloquent events:

### Created Event
```php
$post = Post::create(['title' => 'New Post']);
// ActionLog created with:
// - event: 'created'
// - old_values: null
// - new_values: ['title' => 'New Post', 'id' => 1, ...]
```

### Updated Event
```php
$post->update(['title' => 'Updated Title']);
// ActionLog created with:
// - event: 'updated'
// - old_values: ['title' => 'New Post']
// - new_values: ['title' => 'Updated Title']
// Note: Only changed attributes are logged!
```

### Deleted Event
```php
$post->delete();
// ActionLog created with:
// - event: 'deleted'
// - old_values: [all attributes before deletion]
// - new_values: null
```

### Restored Event (SoftDeletes)
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasActionLogs, SoftDeletes;
}

$post->restore();
// ActionLog created with:
// - event: 'restored'
// - old_values: null
// - new_values: [all current attributes]
```

## Manual Logging

Log custom events manually:

```php
$post = Post::find(1);

// Simple custom event
$post->logAction('published');

// With old/new values
$post->logAction(
    event: 'featured',
    old: ['featured' => false],
    new: ['featured' => true]
);

// With metadata
$post->logAction(
    event: 'shared',
    meta: [
        'platform' => 'twitter',
        'url' => 'https://...',
    ]
);
```

## Querying Logs

### Via Relationship

```php
$post = Post::find(1);

// Get all logs
$logs = $post->actionLogs;

// Eager load
$posts = Post::with('actionLogs')->get();

// Query the relationship
$recentLogs = $post->actionLogs()
    ->where('created_at', '>=', now()->subDays(7))
    ->latest()
    ->get();
```

### Helper Methods

```php
// Get the latest log
$latestLog = $post->latestActionLog();

// Get logs for a specific event
$updates = $post->actionLogsForEvent('updated');

// Check if model has logs
if ($post->hasActionLogs()) {
    echo "Has logs!";
}

// Get count
$count = $post->actionLogsCount();
```

## Customization

### Ignore Specific Attributes

Define attributes to exclude from logging:

```php
class Product extends Model
{
    use HasActionLogs;

    // These attributes won't be logged (in addition to global config)
    protected array $actionLogIgnoredAttributes = [
        'views',
        'last_viewed_at',
        'cache_key',
    ];
}
```

Now updates to these fields won't create logs:

```php
$product->update(['views' => 100]); // Not logged
$product->update(['price' => 29.99]); // Logged!
```

### Custom Actor Resolution

Override who is logged as the actor:

```php
class Order extends Model
{
    use HasActionLogs;

    protected function getActionLogActor(): ?object
    {
        // Use the order processor instead of auth user
        if ($this->processed_by) {
            return User::find($this->processed_by);
        }

        return auth()->user();
    }
}
```

### Conditional Event Logging

Control which events should be logged:

```php
class Comment extends Model
{
    use HasActionLogs;

    protected function shouldLogActionEvent(string $event): bool
    {
        // Don't log spam comments
        if ($this->is_spam) {
            return false;
        }

        // Don't log draft updates
        if ($event === 'updated' && $this->status === 'draft') {
            return false;
        }

        return true;
    }
}
```

## Global Configuration

### Ignore Models Completely

In `config/action-logger.php`:

```php
'ignore_models' => [
    \App\Models\Session::class,
    \App\Models\Cache::class,
],
```

These models won't be logged even if they use the trait.

### Ignore Attributes Globally

```php
'ignore_attributes' => [
    'updated_at',
    'remember_token',
    'password',
    'api_token',
],
```

These attributes are excluded from all models.

### Configure Events

```php
'events' => [
    'created',
    'updated',
    'deleted',
    'restored',
],
```

Only these events will be logged automatically.

## Advanced Examples

### Audit Trail for User Changes

```php
class User extends Model
{
    use HasActionLogs;

    protected array $actionLogIgnoredAttributes = [
        'password',
        'remember_token',
        'last_login_at',
    ];
}

// Later, view user's change history
$user = User::find(1);
$history = $user->actionLogs()
    ->with('actor')
    ->latest()
    ->get();

foreach ($history as $log) {
    echo "{$log->actor->name} {$log->event} user at {$log->created_at}";
    if ($log->isUpdated()) {
        foreach ($log->getChanges() as $field => $change) {
            echo "  {$field}: {$change['old']} → {$change['new']}";
        }
    }
}
```

### Track Product Price Changes

```php
class Product extends Model
{
    use HasActionLogs;
}

// Get all price changes
$product = Product::find(1);
$priceChanges = $product->actionLogs()
    ->forEvent('updated')
    ->get()
    ->filter(function ($log) {
        return isset($log->new_values['price']);
    });

foreach ($priceChanges as $log) {
    $old = $log->old_values['price'] ?? 'N/A';
    $new = $log->new_values['price'];
    echo "Price changed from {$old} to {$new} on {$log->created_at}";
}
```

### Multi-Tenant Audit Logs

```php
// With tenant mode enabled in config
class Document extends Model
{
    use HasActionLogs;

    protected $fillable = ['title', 'content', 'tenant_id'];
}

// All logs automatically include tenant_id
$document = Document::create([
    'title' => 'Contract',
    'tenant_id' => 1,
]);

// Query logs for current tenant
$tenantLogs = ActionLog::forTenant(auth()->user()->tenant_id)
    ->latest()
    ->paginate(20);
```

## Best Practices

1. **Ignore Sensitive Data**: Always exclude passwords, tokens, etc.
   ```php
   protected array $actionLogIgnoredAttributes = ['password', 'api_token'];
   ```

2. **Ignore Timestamps**: Usually `updated_at` isn't meaningful for audits
   ```php
   'ignore_attributes' => ['updated_at', 'last_seen_at'],
   ```

3. **Use Custom Events**: Log business events, not just CRUD
   ```php
   $order->logAction('payment_received', meta: ['amount' => 99.99]);
   ```

4. **Eager Load Logs**: Prevent N+1 queries
   ```php
   $posts = Post::with('actionLogs.actor')->get();
   ```

5. **Index for Performance**: Add indexes on frequently queried fields
   ```php
   // The migration already includes indexes on:
   // - subject_type, subject_id
   // - actor_type, actor_id
   // - event
   // - tenant_id
   ```

## Trait Methods Reference

### Relationship
- `actionLogs(): MorphMany` - Get all logs for this model

### Manual Logging
- `logAction(string $event, ?array $old, ?array $new, ?array $meta): ?ActionLog`

### Query Helpers
- `latestActionLog(): ?ActionLog` - Get the most recent log
- `actionLogsForEvent(string $event): Collection` - Get logs for specific event
- `hasActionLogs(): bool` - Check if model has any logs
- `actionLogsCount(): int` - Get total log count

### Customization Hooks
- `getActionLogActor(): ?object` - Override actor resolution
- `shouldLogActionEvent(string $event): bool` - Control event logging
- `$actionLogIgnoredAttributes` - Model-specific ignored attributes

## See Also

- [Service Usage](./USAGE.md)
- [Examples](../src/Examples/ExampleHasActionLogs.php)
- [Multi-Tenancy](../src/Examples/ExampleCustomTenantResolver.php)
