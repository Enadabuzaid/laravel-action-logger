# Configuration Reference

Complete reference for all configuration options in Laravel Action Logger.

## Configuration File

After installation, the configuration file is located at `config/action-logger.php`.

To publish the configuration:

```bash
php artisan vendor:publish --tag=action-logger-config
```

## All Options

### enabled

Toggle action logging on/off globally.

```php
'enabled' => env('ACTION_LOGGER_ENABLED', true),
```

| Value | Behavior |
|-------|----------|
| `true` | Logging is active |
| `false` | All logging is disabled, `log()` returns `null` |

**Environment Variable:** `ACTION_LOGGER_ENABLED`

**Use Cases:**
- Disable logging in tests
- Disable logging in development
- Emergency disable in production

---

### tenant

Multi-tenancy configuration.

```php
'tenant' => [
    'mode' => env('ACTION_LOGGER_TENANT_MODE', 'none'),
    'column' => env('ACTION_LOGGER_TENANT_COLUMN', 'tenant_id'),
    'resolver' => null,
],
```

#### tenant.mode

| Value | Description |
|-------|-------------|
| `'none'` | No tenant scoping (default) |
| `'column'` | Read tenant ID from model column |
| `'resolver'` | Use custom resolver class |

**Environment Variable:** `ACTION_LOGGER_TENANT_MODE`

#### tenant.column

The column name to read tenant ID from when using `column` mode.

| Value | Example |
|-------|---------|
| `'tenant_id'` | Default |
| `'provider_id'` | For provider-based apps |
| `'organization_id'` | For organization-based apps |

**Environment Variable:** `ACTION_LOGGER_TENANT_COLUMN`

#### tenant.resolver

Custom resolver class or closure for `resolver` mode.

```php
// Class name
'resolver' => \App\Services\MyTenantResolver::class,

// Closure
'resolver' => function ($app) {
    return new \App\Services\MyTenantResolver(
        $app->make(SomeService::class)
    );
},
```

See [Multi-Tenancy Guide](./MULTI-TENANCY.md) for details.

---

### events

Eloquent events to log automatically when using the `HasActionLogs` trait.

```php
'events' => [
    'created',
    'updated',
    'deleted',
    'restored',
],
```

| Event | Trigger |
|-------|---------|
| `'created'` | Model is created |
| `'updated'` | Model is updated |
| `'deleted'` | Model is deleted (including soft delete) |
| `'restored'` | Soft-deleted model is restored |

**Customize per model:**

```php
class Post extends Model
{
    use HasActionLogs;

    protected function shouldLogActionEvent(string $event): bool
    {
        return in_array($event, ['created', 'deleted']);
    }
}
```

---

### ignore_models

Models that should never be logged, even if they use the trait.

```php
'ignore_models' => [
    \App\Models\Session::class,
    \App\Models\Cache::class,
    \App\Models\PasswordReset::class,
],
```

**Use Cases:**
- Session/cache models
- High-frequency update models
- Temporary/transient models

---

### ignore_attributes

Attributes to exclude from logging globally.

```php
'ignore_attributes' => [
    'updated_at',
    'remember_token',
    'password',
    'api_token',
    'last_login_at',
],
```

These attributes will be excluded from `old_values` and `new_values` in all logs.

**Per-model override:**

```php
class User extends Model
{
    use HasActionLogs;

    protected array $actionLogIgnoredAttributes = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];
}
```

---

## UI Configuration

### ui.enabled

Enable/disable the optional Inertia + Vue UI.

```php
'ui' => [
    'enabled' => env('ACTION_LOGGER_UI_ENABLED', false),
],
```

**Environment Variable:** `ACTION_LOGGER_UI_ENABLED`

---

### ui.route_prefix

URL prefix for UI routes.

```php
'ui' => [
    'route_prefix' => env('ACTION_LOGGER_ROUTE_PREFIX', 'action-logs'),
],
```

| Value | Dashboard URL |
|-------|--------------|
| `'action-logs'` | `/action-logs` |
| `'admin/logs'` | `/admin/logs` |
| `'audit'` | `/audit` |

**Environment Variable:** `ACTION_LOGGER_ROUTE_PREFIX`

---

### ui.middleware

Middleware applied to all UI routes.

```php
'ui' => [
    'middleware' => ['web', 'auth'],
],
```

**Common configurations:**

```php
// Require authentication
'middleware' => ['web', 'auth'],

// Require verified email
'middleware' => ['web', 'auth', 'verified'],

// Require admin role (with Spatie Permission)
'middleware' => ['web', 'auth', 'role:admin'],
```

---

### ui.authorize

Authorization callback or Gate name for UI access.

```php
'ui' => [
    // Closure
    'authorize' => function($user) {
        return $user->hasRole('admin');
    },

    // Gate name
    'authorize' => 'viewActionLogs',

    // Null (no authorization check beyond middleware)
    'authorize' => null,
],
```

**Define a Gate:**

```php
// In AuthServiceProvider
Gate::define('viewActionLogs', function ($user) {
    return $user->isAdmin();
});
```

---

### ui.modules

Configure modules (model categories) displayed in the UI.

```php
'ui' => [
    'modules' => [
        'posts' => [
            'label' => 'Post Logs',
            'description' => 'Actions performed on blog posts.',
            'subject_type' => 'App\\Models\\Post',
            'icon' => 'FileText',
        ],
        'users' => [
            'label' => 'User Logs',
            'description' => 'Actions performed on users.',
            'subject_type' => 'App\\Models\\User',
            'icon' => 'Users',
        ],
        'orders' => [
            'label' => 'Order Logs',
            'description' => 'Track order changes and status updates.',
            'subject_type' => 'App\\Models\\Order',
            'icon' => 'ShoppingCart',
        ],
    ],
],
```

**Module Properties:**

| Property | Type | Description |
|----------|------|-------------|
| `label` | string | Display name in UI |
| `description` | string | Short description |
| `subject_type` | string | Fully qualified model class name |
| `icon` | string | Lucide icon name |

**Available Icons:**
- `FileText`, `Users`, `ShoppingCart`, `Tag`, `Briefcase`
- Any [Lucide icon](https://lucide.dev/icons) name

---

### ui.per_page

Number of logs per page in listings.

```php
'ui' => [
    'per_page' => 20,
],
```

| Value | Trade-off |
|-------|-----------|
| `10` | Less scrolling, more page loads |
| `20` | Default balance |
| `50` | Fewer page loads, longer pages |

---

### ui.date_format

PHP date format for displaying timestamps.

```php
'ui' => [
    'date_format' => 'M d, Y H:i',
],
```

| Format | Output |
|--------|--------|
| `'M d, Y H:i'` | `Dec 24, 2024 11:30` |
| `'Y-m-d H:i:s'` | `2024-12-24 11:30:45` |
| `'d/m/Y'` | `24/12/2024` |

---

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `ACTION_LOGGER_ENABLED` | `true` | Enable/disable logging |
| `ACTION_LOGGER_TENANT_MODE` | `'none'` | Tenancy mode |
| `ACTION_LOGGER_TENANT_COLUMN` | `'tenant_id'` | Tenant column name |
| `ACTION_LOGGER_UI_ENABLED` | `false` | Enable UI routes |
| `ACTION_LOGGER_ROUTE_PREFIX` | `'action-logs'` | UI route prefix |

---

## Example Configurations

### Single-Tenant App

```php
return [
    'enabled' => true,
    'tenant' => [
        'mode' => 'none',
    ],
    'events' => ['created', 'updated', 'deleted', 'restored'],
    'ignore_attributes' => ['updated_at', 'remember_token'],
    'ui' => [
        'enabled' => true,
        'middleware' => ['web', 'auth'],
        'authorize' => fn($user) => $user->is_admin,
    ],
];
```

### Multi-Tenant SaaS

```php
return [
    'enabled' => true,
    'tenant' => [
        'mode' => 'column',
        'column' => 'organization_id',
    ],
    'events' => ['created', 'updated', 'deleted'],
    'ignore_models' => [
        \App\Models\Session::class,
    ],
    'ignore_attributes' => [
        'updated_at',
        'remember_token',
        'password',
    ],
    'ui' => [
        'enabled' => true,
        'route_prefix' => 'admin/audit',
        'middleware' => ['web', 'auth', 'verified', 'role:admin'],
        'authorize' => 'viewAuditLogs',
        'per_page' => 30,
    ],
];
```

### Development/Testing

```php
return [
    'enabled' => env('ACTION_LOGGER_ENABLED', false), // Disabled by default
    'tenant' => [
        'mode' => 'none',
    ],
    'events' => ['created', 'updated', 'deleted', 'restored'],
    'ignore_attributes' => ['updated_at'],
    'ui' => [
        'enabled' => true,
        'middleware' => ['web'], // No auth for dev
    ],
];
```

---

## See Also

- [Installation](../README.md#installation)
- [Multi-Tenancy Guide](./MULTI-TENANCY.md)
- [UI Guide](./UI.md)
