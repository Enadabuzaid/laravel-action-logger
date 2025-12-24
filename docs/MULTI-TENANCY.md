# Multi-Tenancy Guide

Laravel Action Logger provides robust multi-tenancy support out of the box. This guide covers all available tenancy modes and how to implement custom solutions.

## Overview

Multi-tenancy allows you to scope action logs to specific tenants (organizations, companies, workspaces, etc.) in your application. The package supports three tenancy modes:

| Mode | Description | Use Case |
|------|-------------|----------|
| `none` | No tenant scoping | Single-tenant applications |
| `column` | Read tenant ID from model column | Column-based multi-tenancy |
| `resolver` | Custom resolver class | Complex tenancy logic |

## Configuration

Configure tenancy in `config/action-logger.php`:

```php
'tenant' => [
    'mode' => env('ACTION_LOGGER_TENANT_MODE', 'none'),
    'column' => env('ACTION_LOGGER_TENANT_COLUMN', 'tenant_id'),
    'resolver' => null,
],
```

## Mode: None (Default)

No tenant scoping - all logs are global.

```php
'tenant' => [
    'mode' => 'none',
],
```

**When to use:**
- Single-tenant applications
- No need to separate logs by organization/workspace

## Mode: Column

Automatically extracts tenant ID from a column on the subject or actor model.

```php
'tenant' => [
    'mode' => 'column',
    'column' => 'tenant_id', // or 'provider_id', 'organization_id', etc.
],
```

### How It Works

1. When logging an action, the resolver checks the **subject** first for the tenant column
2. If not found, it falls back to the **actor**
3. The tenant ID is automatically stored with the log

### Example

```php
// Your model
class Document extends Model
{
    use HasActionLogs;

    protected $fillable = ['title', 'content', 'tenant_id'];
}

// Create a document
$document = Document::create([
    'title' => 'Contract',
    'tenant_id' => 1,
]);

// The action log automatically has tenant_id = 1
```

### Querying by Tenant

```php
use Enadstack\ActionLogger\Models\ActionLog;

// Get logs for current tenant
$tenantLogs = ActionLog::forTenant(auth()->user()->tenant_id)->get();

// Get logs without tenant (global)
$globalLogs = ActionLog::forTenant(null)->get();
```

## Mode: Resolver

Use a custom resolver class for complex tenancy logic.

### Built-in Resolvers

#### RequestTenantResolver

Resolves tenant ID from HTTP requests (headers, route parameters, session):

```php
'tenant' => [
    'mode' => 'resolver',
    'resolver' => \Enadstack\ActionLogger\Tenant\RequestTenantResolver::class,
],
```

The resolver checks in this order:
1. **Header**: `X-Tenant-ID`
2. **Route Parameter**: `tenant`
3. **Session**: `tenant_id`

#### Custom Configuration

```php
// Register in a service provider
$this->app->singleton(TenantResolverInterface::class, function ($app) {
    return new RequestTenantResolver(
        headerKey: 'X-Organization-ID',
        routeParameter: 'org',
        sessionKey: 'organization_id'
    );
});
```

### Creating a Custom Resolver

Implement `TenantResolverInterface`:

```php
<?php

namespace App\Services;

use Enadstack\ActionLogger\Contracts\TenantResolverInterface;

class MyTenantResolver implements TenantResolverInterface
{
    public function resolveTenantId(?object $subject, ?object $actor): int|string|null
    {
        // Your custom logic here
        
        // Example 1: Use a global tenant context
        if (app()->bound('current_tenant')) {
            return app('current_tenant')->id;
        }
        
        // Example 2: Use the authenticated user's tenant
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }
        
        // Example 3: Check the subject for a tenant relation
        if ($subject && method_exists($subject, 'tenant')) {
            return $subject->tenant?->id;
        }
        
        return null;
    }
}
```

Register in config:

```php
'tenant' => [
    'mode' => 'resolver',
    'resolver' => \App\Services\MyTenantResolver::class,
],
```

Or use a closure:

```php
'tenant' => [
    'mode' => 'resolver',
    'resolver' => function ($app) {
        return new \App\Services\MyTenantResolver(
            $app->make(TenantService::class)
        );
    },
],
```

## Integration Examples

### Spatie Laravel Multitenancy

```php
<?php

namespace App\Services;

use Enadstack\ActionLogger\Contracts\TenantResolverInterface;
use Spatie\Multitenancy\Models\Tenant;

class SpatieTenantResolver implements TenantResolverInterface
{
    public function resolveTenantId(?object $subject, ?object $actor): int|string|null
    {
        return Tenant::current()?->id;
    }
}
```

### Stancl/Tenancy

```php
<?php

namespace App\Services;

use Enadstack\ActionLogger\Contracts\TenantResolverInterface;

class StanclTenantResolver implements TenantResolverInterface
{
    public function resolveTenantId(?object $subject, ?object $actor): int|string|null
    {
        return tenant()?->id;
    }
}
```

### Team-Based Tenancy (Laravel Jetstream)

```php
<?php

namespace App\Services;

use Enadstack\ActionLogger\Contracts\TenantResolverInterface;

class TeamTenantResolver implements TenantResolverInterface
{
    public function resolveTenantId(?object $subject, ?object $actor): int|string|null
    {
        // Use the user's current team
        if (auth()->check()) {
            return auth()->user()->current_team_id;
        }
        
        return null;
    }
}
```

## Querying Tenant-Scoped Logs

### Filter by Tenant

```php
use Enadstack\ActionLogger\Models\ActionLog;

// Logs for a specific tenant
$logs = ActionLog::forTenant(1)->get();

// Logs without tenant (global/system)
$systemLogs = ActionLog::forTenant(null)->get();
```

### Combine with Other Scopes

```php
// Recent updates for current tenant
$recentUpdates = ActionLog::forTenant($tenantId)
    ->forEvent('updated')
    ->recent(7)
    ->with(['subject', 'actor'])
    ->latest()
    ->paginate(20);
```

### Cross-Tenant Queries (Admin)

```php
// All logs across tenants (for super-admin)
$allLogs = ActionLog::latest()->paginate(50);

// Group by tenant
$byTenant = ActionLog::selectRaw('tenant_id, count(*) as count')
    ->groupBy('tenant_id')
    ->get();
```

## Prune by Tenant

Remove old logs for a specific tenant:

```bash
# Prune logs older than 90 days for tenant 1
php artisan action-logger:prune --tenant=1 --days=90

# Preview what would be deleted
php artisan action-logger:prune --tenant=1 --days=60 --dry-run
```

## Stats by Tenant

View statistics for a specific tenant:

```bash
# Stats for tenant 1
php artisan action-logger:stats --tenant=1 --days=30
```

## UI Tenant Filtering

When the UI is enabled, you can filter logs by tenant if you have access to multiple tenants.

### Custom Authorization

```php
// config/action-logger.php
'ui' => [
    'authorize' => function($user) {
        // Only show logs for user's tenant
        return $user->hasPermission('view-action-logs');
    },
],
```

## Best Practices

1. **Choose the right mode**: Use `column` for simple cases, `resolver` for complex scenarios

2. **Index the tenant column**: The migration includes an index, but verify it exists:
   ```php
   $table->index('tenant_id', 'action_logs_tenant_id_index');
   ```

3. **Prune by tenant**: Keep logs manageable by pruning old records per tenant

4. **Test thoroughly**: Ensure your resolver works in all scenarios (console, jobs, API, web)

5. **Consider global actions**: Some actions might be global (system events). Return `null` from your resolver for these cases.

## Troubleshooting

### Tenant ID Always Null

1. Check your mode is set correctly
2. Verify the column name matches your models
3. For custom resolvers, add debugging:
   ```php
   public function resolveTenantId(?object $subject, ?object $actor): int|string|null
   {
       $tenantId = // your logic
       
       \Log::debug('Resolved tenant ID', ['id' => $tenantId]);
       
       return $tenantId;
   }
   ```

### Wrong Tenant on Queue Jobs

Queue jobs run in a different process. Ensure your resolver can access tenant context:

```php
// In your job
public function handle()
{
    // Set tenant context before actions
    Tenant::setCurrent($this->tenantId);
    
    // Now actions are logged with correct tenant
    $model->update([...]);
}
```

### Column Not Found

If you get errors about the tenant column:

1. Ensure the column exists on your models
2. The resolver only checks if the column is accessible, not if it's in the DB schema

## See Also

- [Configuration Reference](./CONFIGURATION.md)
- [API Reference](./API.md)
- [Commands](./COMMANDS.md)
