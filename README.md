<p align="center">
  <img src="https://raw.githubusercontent.com/enadstack/laravel-action-logger/main/art/logo.svg" width="200" alt="Laravel Action Logger">
</p>

<p align="center">
  <a href="https://packagist.org/packages/enadstack/laravel-action-logger"><img src="https://img.shields.io/packagist/v/enadstack/laravel-action-logger.svg?style=flat-square" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/enadstack/laravel-action-logger"><img src="https://img.shields.io/packagist/dt/enadstack/laravel-action-logger.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://github.com/enadstack/laravel-action-logger/actions"><img src="https://img.shields.io/github/actions/workflow/status/enadstack/laravel-action-logger/tests.yml?branch=main&style=flat-square" alt="Tests"></a>
  <a href="https://github.com/enadstack/laravel-action-logger/blob/main/LICENSE"><img src="https://img.shields.io/packagist/l/enadstack/laravel-action-logger.svg?style=flat-square" alt="License"></a>
</p>

# Laravel Action Logger

A powerful, flexible, and developer-friendly Action Logger / Audit Log package for Laravel. Track all changes to your Eloquent models, support multi-tenancy out of the box, and optionally visualize logs with a beautiful Inertia + Vue 3 UI.

## ✨ Features

| Feature | Description |
|---------|-------------|
| 🔍 **Automatic CRUD Logging** | Log `created`, `updated`, `deleted`, `restored` events automatically |
| 📝 **Smart Diff Tracking** | Only logs changed attributes on updates |
| 🏢 **Multi-Tenant Ready** | Column-based, request-based, or custom resolver |
| 🎯 **Fluent API** | Both direct method and fluent builder patterns |
| 🔎 **Rich Query Scopes** | Filter by subject, actor, event, date, tenant, and more |
| 🎨 **Optional UI** | Beautiful Inertia + Vue 3 + shadcn-vue dashboard |
| 🔧 **Highly Configurable** | Ignore attributes, models, events, and more |
| ⚡ **Laravel 11 & 12** | Full compatibility with latest Laravel versions |
| 🧪 **Fully Tested** | 55+ automated tests |

## 📋 Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.2 or higher |
| Laravel | 11.0 or 12.0 |

## 🚀 Installation

### Step 1: Install via Composer

```bash
composer require enadstack/laravel-action-logger
```

### Step 2: Run the Install Command

```bash
php artisan action-logger:install
```

This will:
- ✅ Publish the configuration file
- ✅ Publish the migration
- ✅ Optionally run the migration
- ✅ Display next steps

### Alternative: Manual Installation

```bash
# Publish config
php artisan vendor:publish --tag=action-logger-config

# Publish migration
php artisan vendor:publish --tag=action-logger-migrations

# Run migration
php artisan migrate
```

## 📖 Quick Start

### 1. Add the Trait to Your Models

```php
use Enadstack\ActionLogger\Traits\HasActionLogs;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasActionLogs;
}
```

That's it! Now all CRUD operations are automatically logged:

```php
// Logged: created
$post = Post::create(['title' => 'My First Post']);

// Logged: updated (only changed fields)
$post->update(['title' => 'Updated Title']);

// Logged: deleted
$post->delete();
```

### 2. Query the Logs

```php
// Get all logs for a model
$logs = $post->actionLogs;

// Get the latest log
$latestLog = $post->latestActionLog();

// Get changes from an update
if ($latestLog->isUpdated()) {
    foreach ($latestLog->getChanges() as $field => $change) {
        echo "{$field}: {$change['old']} → {$change['new']}";
    }
}
```

### 3. Log Custom Events

```php
// Simple custom event
$post->logAction('published');

// With metadata
$post->logAction('shared', meta: [
    'platform' => 'twitter',
    'url' => 'https://...',
]);
```

## 📚 Documentation

| Document | Description |
|----------|-------------|
| 📘 [Usage Guide](docs/USAGE.md) | ActionLogger service and facade usage |
| 📗 [Trait Documentation](docs/TRAIT.md) | HasActionLogs trait complete guide |
| 📖 [API Reference](docs/API.md) | Complete API documentation |
| 🔧 [Commands](docs/COMMANDS.md) | Artisan commands reference |
| 🎨 [UI Guide](docs/UI.md) | Optional Inertia + Vue UI setup |
| 📋 [Quick Reference](docs/QUICK-REFERENCE.md) | Cheat sheet for common tasks |
| 🏢 [Multi-Tenancy](docs/MULTI-TENANCY.md) | Multi-tenant configuration guide |
| ⚙️ [Configuration](docs/CONFIGURATION.md) | Complete configuration reference |
| 🔄 [Changelog](CHANGELOG.md) | Version history |

## 🔧 Configuration

After installation, you can customize behavior in `config/action-logger.php`:

### Enable/Disable Logging

```php
// config/action-logger.php
'enabled' => env('ACTION_LOGGER_ENABLED', true),
```

### Configure Events

```php
'events' => [
    'created',
    'updated',
    'deleted',
    'restored',
],
```

### Ignore Attributes

```php
'ignore_attributes' => [
    'updated_at',
    'remember_token',
    'password',
],
```

### Multi-Tenancy

```php
'tenant' => [
    'mode' => 'column', // 'none', 'column', or 'resolver'
    'column' => 'tenant_id',
],
```

## 🏢 Multi-Tenancy Support

The package supports three tenancy modes:

### 1. No Tenancy (Default)

```php
'tenant' => [
    'mode' => 'none',
],
```

### 2. Column-Based Tenancy

Automatically reads tenant ID from your models:

```php
'tenant' => [
    'mode' => 'column',
    'column' => 'tenant_id', // or 'provider_id', 'organization_id', etc.
],
```

### 3. Custom Resolver

Use your own logic to determine tenant context:

```php
'tenant' => [
    'mode' => 'resolver',
    'resolver' => \App\Services\MyTenantResolver::class,
],
```

See [Multi-Tenancy Guide](docs/MULTI-TENANCY.md) for complete documentation.

### Context-object tenancy

```php
// AppServiceProvider::boot(), with action-logger.tenant.mode = 'resolver'
ActionLogger::resolveTenantUsing(fn ($subject, $actor) => TenantContext::id()); // null for platform admins
```

See [docs/MULTI-TENANCY.md](docs/MULTI-TENANCY.md) for scoping, `platform_ability` and `action-logger:prune --tenant=<id>`.

## 🎨 Optional UI

The package includes a beautiful Inertia + Vue 3 + shadcn-vue UI for viewing logs.

### Enable the UI

```bash
# In .env
ACTION_LOGGER_UI_ENABLED=true
```

### Publish UI Components (Optional)

```bash
php artisan vendor:publish --tag=action-logger-ui
```

### Access the Dashboard

Visit `/action-logs` to see:

- 📊 **Dashboard** - Statistics and recent activity
- 📋 **All Logs** - Filterable, paginated log viewer
- 📦 **Module Logs** - Logs grouped by model type
- 🔍 **Record Timeline** - Complete history for any record

See [UI Guide](docs/UI.md) for full documentation.

## 🔎 Advanced Usage

### Fluent API

```php
use Enadstack\ActionLogger\Facades\ActionLogger;

ActionLogger::for($post)
    ->by($user)
    ->event('featured')
    ->withOld(['featured' => false])
    ->withNew(['featured' => true])
    ->addMeta('reason', 'Editor choice')
    ->save();
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

### Rich Querying

```php
use Enadstack\ActionLogger\Models\ActionLog;

// Combine multiple scopes
$logs = ActionLog::forSubject($post)
    ->forEvents(['created', 'updated'])
    ->recent(30)
    ->userActions()
    ->with('actor')
    ->latest()
    ->paginate(20);

// Track price changes
$priceHistory = $product->actionLogs()
    ->forEvent('updated')
    ->get()
    ->filter(fn($log) => $log->hasChangeFor('price'));
```

## 🧾 Audit Metadata (1.1)

```php
ActionLogger::log($subject, $actor, 'roles.assigned', null, ['role' => 'admin'], null,
    module: 'roles', severity: 'warning');            // severity: info (default) | warning | critical

ActionLogger::for($subject)->event('users.suspended')->module('users')->severity('critical')->save();

ActionLogger::log(null, null, 'permissions.synced', module: 'permissions'); // subject-less event
```

- `request_id` is a valid `X-Request-ID` header (`[A-Za-z0-9._\-:]`, 1-64 chars; invalid values are ignored), otherwise a uuid generated once per HTTP request. In console/queue/scheduler contexts without a valid header it stays null. A valid `requestId:` argument overrides; an invalid one is ignored.
- Scopes: `ActionLog::forModule('roles')`, `::severity('warning')`, `::forRequest($id)`.
- Immutability: set `ACTION_LOGGER_IMMUTABLE=true` to make model `update()`/`delete()` throw `ImmutableLogException`. Bulk query-builder deletes and updates, upserts and truncate (including `action-logger:prune`) bypass model events and are not blocked.
- Morph id columns (`subject_id`, `actor_id`) remain bigint.
- Snapshot labels: `subject_label` / `actor_label` (190 chars, multibyte-safe) are stored with each entry so a deleted subject or actor still renders. They are derived from `name`, then `email`, then `title`, or passed explicitly: `ActionLogger::log($m, $u, 'x.y', subjectLabel: 'Invoice 7', actorLabel: 'Cron')`. Rows written before 1.2.0 have null labels.
- Redaction: values of keys listed in `action-logger.redact` (case-insensitive, any depth) are replaced with `[redacted]` in `old_values`, `new_values` and `meta` BEFORE they are stored, so secrets never reach the table.
- Retention under immutability: model `update()`/`delete()` throw, while `action-logger:prune` (bulk delete) remains the documented retention path.

## 🔨 Maintenance

### Prune Old Logs

```bash
# Remove logs older than 90 days
php artisan action-logger:prune --days=90

# Preview (dry run)
php artisan action-logger:prune --days=60 --dry-run

# Prune for specific tenant
php artisan action-logger:prune --tenant=1 --days=90
```

### View Statistics

```bash
# Stats for last 30 days
php artisan action-logger:stats

# Stats for last 7 days
php artisan action-logger:stats --days=7
```

### Schedule Automatic Pruning

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('action-logger:prune --days=90')
        ->weekly()
        ->sundays()
        ->at('02:00');
}
```

## 🧪 Testing

The package includes a comprehensive test suite:

```bash
# Run all tests
composer test

# Run with PHPUnit directly
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature

# Generate coverage report
vendor/bin/phpunit --coverage-html coverage
```

## 💡 Examples

Check out the example files in `examples/`:

- `ExampleUsage.php` - Service and facade usage examples
- `ExampleHasActionLogs.php` - Trait usage and customization
- `ExampleCustomTenantResolver.php` - Custom tenant resolver implementation

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## 📄 License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

## 🙏 Credits

- [Enad Abu Zaid](https://github.com/enadabuzaid)
- [All Contributors](../../contributors)

---

<p align="center">
Made with ❤️ by <a href="https://enadstack.com">EnadStack</a>
</p>
