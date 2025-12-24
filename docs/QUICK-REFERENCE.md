# Quick Reference

A cheat sheet for common Laravel Action Logger tasks.

## Installation

```bash
# Install package
composer require enadstack/laravel-action-logger

# Run installer
php artisan action-logger:install
```

## Add Trait to Model

```php
use Enadstack\ActionLogger\Traits\HasActionLogs;

class Post extends Model
{
    use HasActionLogs;
}
```

## Automatic Logging

```php
Post::create(['title' => 'New']); // Logged: created
$post->update(['title' => 'Updated']); // Logged: updated
$post->delete(); // Logged: deleted
$post->restore(); // Logged: restored (if SoftDeletes)
```

## Manual Logging

### Via Trait Method

```php
$post->logAction('published');

$post->logAction('featured', meta: [
    'reason' => 'Editor pick',
]);
```

### Via Facade

```php
use Enadstack\ActionLogger\Facades\ActionLogger;

ActionLogger::log($post, $user, 'viewed');

// Fluent API
ActionLogger::for($post)
    ->by($user)
    ->event('exported')
    ->withMeta(['format' => 'pdf'])
    ->save();
```

### Via Helper

```php
log_action($post, $user, 'archived');
```

## Query Logs

### From Model

```php
$post->actionLogs; // All logs
$post->latestActionLog(); // Latest log
$post->firstActionLog(); // First log (creation)
$post->actionLogsForEvent('updated'); // By event
$post->recentActionLogs(7); // Last 7 days
$post->paginatedActionLogs(20); // Paginated
$post->actionTimeline(50); // Formatted timeline
```

### From ActionLog Model

```php
use Enadstack\ActionLogger\Models\ActionLog;

ActionLog::forSubject($post)->get();
ActionLog::forActor($user)->get();
ActionLog::forEvent('deleted')->get();
ActionLog::forEvents(['created', 'updated'])->get();
ActionLog::betweenDates('2024-01-01', '2024-12-31')->get();
ActionLog::forTenant($tenantId)->get();
ActionLog::recent(7)->get();
ActionLog::today()->get();
ActionLog::systemActions()->get(); // No actor
ActionLog::userActions()->get(); // Has actor
```

### Combine Scopes

```php
ActionLog::forSubject($post)
    ->forEvent('updated')
    ->recent(30)
    ->with('actor')
    ->latest()
    ->paginate(20);
```

## Analyze Changes

```php
$log = $post->latestActionLog();

// Check event type
$log->isCreated(); // bool
$log->isUpdated(); // bool
$log->isDeleted(); // bool
$log->isRestored(); // bool

// Get changes
$log->getChanges();
// ['title' => ['old' => 'Old', 'new' => 'New']]

$log->getChangeFor('title');
// ['old' => 'Old', 'new' => 'New']

$log->hasChangeFor('title'); // bool
$log->getChangedAttributes(); // ['title', 'status']

// Display helpers
$log->getDescription(); // "User updated Post #123"
$log->getEventLabel(); // "Updated"
$log->getTimeAgo(); // "2 hours ago"
$log->getEventColor(); // "blue"
$log->getEventIcon(); // "edit"
```

## Actor Tracking

```php
$post->wasCreatedBy($user); // bool
$post->wasLastModifiedBy($user); // bool
$post->getCreator(); // User|null
$post->getLastModifier(); // User|null
$post->getUniqueActors(); // Collection of users
```

## Metadata

```php
// Add metadata when logging
$post->logAction('shared', meta: [
    'platform' => 'twitter',
    'url' => 'https://...',
]);

// Read metadata
$log->meta; // array
$log->getMeta('platform'); // "twitter"
$log->getMeta('missing', 'default'); // "default"
$log->hasMeta('platform'); // bool
```

## Customization

### Ignore Attributes (Model)

```php
class User extends Model
{
    use HasActionLogs;

    protected array $actionLogIgnoredAttributes = [
        'password',
        'remember_token',
    ];
}
```

### Custom Actor Resolution

```php
class Order extends Model
{
    use HasActionLogs;

    protected function getActionLogActor(): ?object
    {
        return User::find($this->processed_by) ?? auth()->user();
    }
}
```

### Conditional Logging

```php
class Comment extends Model
{
    use HasActionLogs;

    protected function shouldLogActionEvent(string $event): bool
    {
        return !$this->is_spam;
    }
}
```

## Configuration

```php
// config/action-logger.php

'enabled' => true,

'tenant' => [
    'mode' => 'none', // 'column', 'resolver'
    'column' => 'tenant_id',
],

'events' => ['created', 'updated', 'deleted', 'restored'],

'ignore_attributes' => ['updated_at', 'remember_token'],

'ignore_models' => [
    \App\Models\Session::class,
],
```

## Environment Variables

```bash
ACTION_LOGGER_ENABLED=true
ACTION_LOGGER_TENANT_MODE=column
ACTION_LOGGER_TENANT_COLUMN=tenant_id
ACTION_LOGGER_UI_ENABLED=true
ACTION_LOGGER_ROUTE_PREFIX=action-logs
```

## Artisan Commands

```bash
# Install
php artisan action-logger:install

# Prune old logs
php artisan action-logger:prune --days=90
php artisan action-logger:prune --dry-run # Preview
php artisan action-logger:prune --tenant=1 # By tenant

# View stats
php artisan action-logger:stats
php artisan action-logger:stats --days=7
php artisan action-logger:stats --tenant=1
```

## Enable UI

```bash
# .env
ACTION_LOGGER_UI_ENABLED=true
```

```bash
# Optional: publish Vue components
php artisan vendor:publish --tag=action-logger-ui
```

Visit: `/action-logs`

## Multi-Tenancy

### Column Mode

```php
'tenant' => [
    'mode' => 'column',
    'column' => 'tenant_id',
],
```

### Custom Resolver

```php
'tenant' => [
    'mode' => 'resolver',
    'resolver' => \App\Services\MyTenantResolver::class,
],
```

```php
class MyTenantResolver implements TenantResolverInterface
{
    public function resolveTenantId(?object $subject, ?object $actor): int|string|null
    {
        return auth()->user()?->tenant_id;
    }
}
```

## API Response

```php
$log->toApiArray();
/*
[
    'id' => 1,
    'event' => 'updated',
    'event_label' => 'Updated',
    'description' => 'User updated Post #123',
    'subject' => ['type' => 'Post', 'id' => 123],
    'actor' => ['type' => 'User', 'id' => 1, 'name' => 'John'],
    'changes' => [...],
    'meta' => [...],
    'ip_address' => '192.168.1.1',
    'created_at' => '2024-12-24T10:30:00Z',
    'time_ago' => '2 hours ago',
]
*/
```

## Scheduled Pruning

```php
// app/Console/Kernel.php
$schedule->command('action-logger:prune --days=90')
    ->weekly()
    ->sundays()
    ->at('02:00');
```

## Testing

```bash
composer test
vendor/bin/phpunit
vendor/bin/phpunit --coverage-html coverage
```

## Links

- 📖 [Full Documentation](../README.md)
- 🔧 [API Reference](./API.md)
- 🏢 [Multi-Tenancy Guide](./MULTI-TENANCY.md)
- ⚙️ [Configuration Reference](./CONFIGURATION.md)
