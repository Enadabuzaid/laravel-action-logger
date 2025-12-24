# Public API Reference

Complete reference for all public methods and query helpers in the Action Logger package.

## ActionLog Model

### Query Scopes

#### Basic Scopes

```php
use Enadstack\ActionLogger\Models\ActionLog;

// Filter by subject (model)
ActionLog::forSubject($post)->get();

// Filter by actor (user)
ActionLog::forActor($user)->get();

// Filter by single event
ActionLog::forEvent('updated')->get();

// Filter by multiple events
ActionLog::forEvents(['created', 'updated'])->get();

// Filter by date range
ActionLog::betweenDates('2024-01-01', '2024-12-31')->get();

// Filter by tenant
ActionLog::forTenant($tenantId)->get();
```

#### Time-Based Scopes

```php
// Recent logs (last 7 days by default)
ActionLog::recent()->get();
ActionLog::recent(30)->get(); // Last 30 days

// Today's logs only
ActionLog::today()->get();
```

#### Actor-Based Scopes

```php
// System actions only (no actor)
ActionLog::systemActions()->get();

// User actions only (has actor)
ActionLog::userActions()->get();
```

#### Advanced Scopes

```php
// Logs with changes to a specific attribute
ActionLog::withAttributeChange('price')->get();

// Logs from a specific IP
ActionLog::fromIp('192.168.1.1')->get();

// Logs with metadata
ActionLog::withMeta()->get();

// Logs with a specific meta key
ActionLog::withMetaKey('reason')->get();
```

#### Combining Scopes

```php
// All scopes are chainable
$logs = ActionLog::forSubject($post)
    ->forEvent('updated')
    ->recent(30)
    ->userActions()
    ->with('actor')
    ->latest()
    ->paginate(20);
```

### Instance Methods

#### Event Type Checks

```php
$log = ActionLog::find(1);

$log->isCreated();      // bool
$log->isUpdated();      // bool
$log->isDeleted();      // bool
$log->isRestored();     // bool
$log->isSystemAction(); // bool
$log->isUserAction();   // bool
```

#### Change Analysis

```php
// Get all changes
$changes = $log->getChanges();
// ['title' => ['old' => 'Old', 'new' => 'New']]

// Get change for specific attribute
$change = $log->getChangeFor('title');
// ['old' => 'Old', 'new' => 'New'] or null

// Check if attribute changed
$log->hasChangeFor('title'); // bool

// Get all changed attribute names
$attributes = $log->getChangedAttributes();
// ['title', 'status']
```

#### Metadata Access

```php
// Get specific meta value
$value = $log->getMeta('reason', 'No reason provided');

// Check if meta key exists
$log->hasMeta('reason'); // bool

// Access all meta
$meta = $log->meta; // array
```

#### Formatting & Display

```php
// Human-readable description
$log->getDescription();
// "User updated Post #123"

// Event label
$log->getEventLabel();
// "Updated" (from "updated")

// Time ago
$log->getTimeAgo();
// "2 hours ago"

// Formatted date
$log->getFormattedDate('M d, Y');
// "Dec 20, 2024"

// Event color (for UI badges)
$log->getEventColor();
// "green", "blue", "red", "yellow", "gray"

// Event icon (for UI)
$log->getEventIcon();
// "plus-circle", "edit", "trash", "refresh", "activity"
```

#### API Responses

```php
// Convert to API-friendly array
$array = $log->toApiArray();
/*
[
    'id' => 1,
    'event' => 'updated',
    'event_label' => 'Updated',
    'description' => 'User updated Post #123',
    'subject' => ['type' => 'Post', 'id' => 123],
    'actor' => ['type' => 'User', 'id' => 1, 'name' => 'John Doe'],
    'changes' => [...],
    'meta' => [...],
    'ip_address' => '192.168.1.1',
    'created_at' => '2024-12-20T10:30:00Z',
    'time_ago' => '2 hours ago',
]
*/
```

## HasActionLogs Trait

### Relationship

```php
// Get all logs
$post->actionLogs; // Collection

// Query the relationship
$post->actionLogs()->latest()->get();
```

### Manual Logging

```php
// Simple event
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
    meta: ['platform' => 'twitter']
);
```

### Query Helpers

#### Basic Queries

```php
// Latest log
$post->latestActionLog(); // ActionLog|null

// First log (creation)
$post->firstActionLog(); // ActionLog|null

// Logs for specific event
$post->actionLogsForEvent('updated'); // Collection

// Check if has logs
$post->hasActionLogs(); // bool

// Count logs
$post->actionLogsCount(); // int
```

#### Filtered Queries

```php
// Logs by specific actor
$post->actionLogsByActor($user); // Collection

// Logs in date range
$post->actionLogsBetweenDates('2024-01-01', '2024-12-31'); // Collection

// Recent logs (last 7 days by default)
$post->recentActionLogs(); // Collection
$post->recentActionLogs(30); // Last 30 days

// Paginated logs
$post->paginatedActionLogs(20); // LengthAwarePaginator
```

#### Grouped & Formatted

```php
// Logs grouped by event
$grouped = $post->actionLogsByEvent();
/*
[
    'created' => [ActionLog, ...],
    'updated' => [ActionLog, ...],
]
*/

// Timeline (formatted for display)
$timeline = $post->actionTimeline(50);
/*
[
    [
        'id' => 1,
        'event' => 'updated',
        'description' => '...',
        'actor' => User,
        'changes' => [...],
        'meta' => [...],
        'created_at' => Carbon,
        'is_created' => false,
        'is_updated' => true,
        ...
    ],
    ...
]
*/
```

### Actor Analysis

```php
// Check if created by specific user
$post->wasCreatedBy($user); // bool

// Check if last modified by specific user
$post->wasLastModifiedBy($user); // bool

// Get creator
$creator = $post->getCreator(); // User|null

// Get last modifier
$modifier = $post->getLastModifier(); // User|null

// Get all unique actors
$actors = $post->getUniqueActors(); // Collection
```

### Modification Tracking

```php
// Check if modified since creation
$post->hasBeenModified(); // bool

// Count updates
$post->updateCount(); // int
```

## ActionLogger Service

### Direct API

```php
use Enadstack\ActionLogger\Facades\ActionLogger;

ActionLogger::log(
    subject: $post,
    actor: $user,
    event: 'published',
    oldValues: ['status' => 'draft'],
    newValues: ['status' => 'published'],
    meta: ['reason' => 'Ready']
);
```

### Fluent API

```php
ActionLogger::for($post)
    ->by($user)
    ->event('published')
    ->withOld(['status' => 'draft'])
    ->withNew(['status' => 'published'])
    ->addMeta('reason', 'Ready')
    ->save();
```

### Helper Functions

```php
// Direct logging
log_action($post, $user, 'viewed');

// Get service instance
$logger = action_logger();
$logger->for($post)->by($user)->event('clicked')->save();
```

## Usage Examples

### Audit Trail

```php
// Get complete audit trail for a model
$audit = $post->actionTimeline()
    ->map(function ($item) {
        return [
            'timestamp' => $item['created_at']->format('Y-m-d H:i:s'),
            'user' => $item['actor']?->name ?? 'System',
            'action' => $item['event_label'],
            'changes' => collect($item['changes'])
                ->map(fn($change, $field) => 
                    "{$field}: {$change['old']} → {$change['new']}"
                )
                ->join(', '),
        ];
    });
```

### Activity Dashboard

```php
// Recent activity across all models
$recentActivity = ActionLog::recent(7)
    ->with(['subject', 'actor'])
    ->latest()
    ->paginate(50);

// Today's activity by event type
$todayStats = ActionLog::today()
    ->get()
    ->groupBy('event')
    ->map->count();
// ['created' => 15, 'updated' => 42, 'deleted' => 3]
```

### User Activity Report

```php
// All actions by a user
$userActivity = ActionLog::forActor($user)
    ->with('subject')
    ->latest()
    ->get();

// User's actions today
$todayActions = ActionLog::forActor($user)
    ->today()
    ->get()
    ->groupBy('event');
```

### Price Change History

```php
// Track all price changes for a product
$priceHistory = $product->actionLogs()
    ->forEvent('updated')
    ->get()
    ->filter(fn($log) => $log->hasChangeFor('price'))
    ->map(function ($log) {
        $change = $log->getChangeFor('price');
        return [
            'date' => $log->created_at,
            'old_price' => $change['old'],
            'new_price' => $change['new'],
            'changed_by' => $log->actor?->name,
        ];
    });
```

### Security Audit

```php
// Suspicious activity from an IP
$suspiciousLogs = ActionLog::fromIp('192.168.1.100')
    ->forEvents(['deleted', 'updated'])
    ->recent(1)
    ->with(['subject', 'actor'])
    ->get();

// System actions (automated changes)
$systemActions = ActionLog::systemActions()
    ->recent(7)
    ->get();
```

## Best Practices

1. **Use Scopes for Readability**
   ```php
   // Good
   ActionLog::forSubject($post)->recent()->get();
   
   // Avoid
   ActionLog::where('subject_id', $post->id)->where(...)->get();
   ```

2. **Eager Load Relationships**
   ```php
   $logs = ActionLog::with(['subject', 'actor'])->recent()->get();
   ```

3. **Use Helper Methods**
   ```php
   // Good
   $post->recentActionLogs();
   
   // Avoid
   $post->actionLogs()->where('created_at', '>=', now()->subDays(7))->get();
   ```

4. **Leverage Timeline for UI**
   ```php
   $timeline = $post->actionTimeline(50);
   // Already formatted with all needed data
   ```

5. **Use toApiArray() for APIs**
   ```php
   return response()->json([
       'logs' => $logs->map->toApiArray(),
   ]);
   ```
