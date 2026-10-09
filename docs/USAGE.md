# ActionLogger Service & Facade Usage

This document provides comprehensive examples of using the ActionLogger service.

## Installation & Setup

The service is automatically registered via the service provider. No additional setup required.

## Usage Methods

You can use the ActionLogger in three ways:

### 1. Via Facade

```php
use Enadstack\ActionLogger\Facades\ActionLogger;

ActionLogger::log($post, $user, 'updated');
```

### 2. Via Helper Function

```php
log_action($post, $user, 'created');

// Or get the service instance
$logger = action_logger();
```

### 3. Via Dependency Injection

```php
use Enadstack\ActionLogger\Services\ActionLogger;

public function __construct(
    protected ActionLogger $logger
) {}

public function someMethod()
{
    $this->logger->log($post, $user, 'deleted');
}
```

## Direct Method Call

```php
use Enadstack\ActionLogger\Facades\ActionLogger;

// Simple log
ActionLogger::log(
    subject: $post,
    actor: $user,
    event: 'viewed'
);

// With old and new values
ActionLogger::log(
    subject: $post,
    actor: $user,
    event: 'updated',
    oldValues: ['title' => 'Old Title'],
    newValues: ['title' => 'New Title']
);

// With metadata
ActionLogger::log(
    subject: $post,
    actor: $user,
    event: 'published',
    meta: ['scheduled_at' => now()->addHours(2)]
);
```

## Fluent Builder Pattern

```php
use Enadstack\ActionLogger\Facades\ActionLogger;

// Basic fluent usage
ActionLogger::for($post)
    ->by($user)
    ->event('deleted')
    ->save();

// With values
ActionLogger::for($post)
    ->by($user)
    ->event('updated')
    ->withOld(['status' => 'draft'])
    ->withNew(['status' => 'published'])
    ->save();

// With metadata
ActionLogger::for($post)
    ->by($user)
    ->event('exported')
    ->addMeta('format', 'pdf')
    ->addMeta('size', '2.5MB')
    ->save();
```

## System Actions (No Actor)

```php
// Automated actions
ActionLogger::log(
    subject: $post,
    actor: null, // System action
    event: 'auto_archived',
    meta: ['reason' => 'Inactive for 90 days']
);

// Or fluent
ActionLogger::for($post)
    ->by(null)
    ->event('auto_published')
    ->save();
```

## Audit Metadata (1.1)

```php
ActionLogger::log($subject, $actor, 'roles.assigned', null, ['role' => 'admin'], null,
    module: 'roles', severity: 'warning');            // severity: info (default) | warning | critical

ActionLogger::for($subject)->event('users.suspended')->module('users')->severity('critical')->save();

ActionLogger::log(null, null, 'permissions.synced', module: 'permissions'); // subject-less event
```

- `request_id` is taken from the `X-Request-ID` header, otherwise a uuid generated once per request (null outside a request). Pass `requestId:` to override.
- Scopes: `ActionLog::forModule('roles')`, `::severity('warning')`, `::forRequest($id)`.
- Immutability: set `ACTION_LOGGER_IMMUTABLE=true` to make model `update()`/`delete()` throw `ImmutableLogException`. Bulk query-builder deletes (including `action-logger:prune`) bypass model events and still work.
- Morph id columns (`subject_id`, `actor_id`) remain bigint.

## Custom Events

You can log any custom event name:

```php
ActionLogger::log($post, $user, 'approved_by_editor');
ActionLogger::log($post, $user, 'flagged_for_review');
ActionLogger::log($post, $user, 'shared_on_social_media');
ActionLogger::log($post, $user, 'sent_for_translation');
```

## Querying Logs

```php
use Enadstack\ActionLogger\Models\ActionLog;

// All logs for a model
$logs = ActionLog::forSubject($post)->get();

// Logs by a specific user
$userLogs = ActionLog::forActor($user)->get();

// Specific event type
$deletions = ActionLog::forEvent('deleted')->get();

// Combine scopes
$recentUpdates = ActionLog::forSubject($post)
    ->forEvent('updated')
    ->betweenDates(now()->subDays(7), now())
    ->latest()
    ->get();

// With tenant filtering
$tenantLogs = ActionLog::forTenant($tenantId)
    ->forEvent('deleted')
    ->get();
```

## Analyzing Changes

```php
$log = ActionLog::find(1);

// Check event type
if ($log->isUpdated()) {
    // Get the changes
    $changes = $log->getChanges();
    // Returns: ['title' => ['old' => 'Old', 'new' => 'New']]
    
    foreach ($changes as $field => $change) {
        echo "{$field} changed from {$change['old']} to {$change['new']}";
    }
}

// Get description
echo $log->getDescription();
// Output: "User updated Post #123"
```

## Configuration

### Disable Logging

```php
// In config/action-logger.php
'enabled' => false,

// Or via environment
ACTION_LOGGER_ENABLED=false
```

When disabled, all `log()` calls return `null` without creating records.

### Multi-Tenancy

The service automatically resolves tenant ID based on your configuration:

```php
// config/action-logger.php
'tenant' => [
    'mode' => 'column', // or 'resolver'
    'column' => 'tenant_id',
],
```

The tenant ID is automatically attached to every log entry.

## Advanced Features

### Request Context

IP address and user agent are automatically captured from the current request:

```php
$log = ActionLogger::log($post, $user, 'viewed');

echo $log->ip_address; // "192.168.1.1"
echo $log->user_agent; // "Mozilla/5.0..."
```

### Metadata

Store any additional context:

```php
ActionLogger::for($post)
    ->by($user)
    ->event('exported')
    ->addMeta('format', 'pdf')
    ->addMeta('pages', 45)
    ->addMeta('file_size', '2.5MB')
    ->addMeta('download_url', 'https://...')
    ->save();
```

### Builder Reset

The fluent builder automatically resets after `save()`, but you can manually reset:

```php
$logger = ActionLogger::for($post)->by($user);
$logger->reset(); // Clear all builder state
```

## Best Practices

1. **Use meaningful event names**: `'approved_by_editor'` instead of `'action1'`
2. **Include context in metadata**: Add relevant information for auditing
3. **Log business events**: Not just CRUD operations
4. **Use system actor for automated actions**: Set `actor` to `null`
5. **Query efficiently**: Use scopes and indexes for better performance

## See Also

- [Full Examples](../examples/ExampleUsage.php)
- [Custom Tenant Resolvers](../examples/ExampleCustomTenantResolver.php)
