# Installation Guide

Complete guide to installing and configuring Laravel Action Logger.

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.2 or higher |
| Laravel | 11.0 or 12.0 |

## Quick Installation

### Step 1: Install via Composer

```bash
composer require enadstack/laravel-action-logger
```

### Step 2: Run the Install Command

```bash
php artisan action-logger:install
```

The installer will:

1. ✅ Publish the configuration file to `config/action-logger.php`
2. ✅ Publish the migration file
3. ✅ Ask if you want to run the migration
4. ✅ Display next steps and usage instructions

### Step 3: Add Trait to Models

```php
use Enadstack\ActionLogger\Traits\HasActionLogs;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasActionLogs;
}
```

That's it! Your model is now being logged.

## Manual Installation

If you prefer to install manually:

### 1. Publish Configuration

```bash
php artisan vendor:publish --tag=action-logger-config
```

This creates `config/action-logger.php`.

### 2. Publish Migration

```bash
php artisan vendor:publish --tag=action-logger-migrations
```

This creates the migration file in `database/migrations/`.

### 3. Run Migration

```bash
php artisan migrate
```

This creates the `action_logs` table.

## CI/CD Installation

For automated deployments:

```bash
php artisan action-logger:install --no-interaction --migrate
```

Flags:
- `--no-interaction`: Skip all prompts
- `--migrate`: Run migrations automatically
- `--force`: Overwrite existing files

## Database Schema

The migration creates an `action_logs` table with the following structure:

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `subject_type` | string | Model class name |
| `subject_id` | bigint | Model ID |
| `actor_type` | string (nullable) | User class name |
| `actor_id` | bigint (nullable) | User ID |
| `event` | string | Event name (created, updated, etc.) |
| `old_values` | json (nullable) | Previous state |
| `new_values` | json (nullable) | New state |
| `meta` | json (nullable) | Additional metadata |
| `ip_address` | string (nullable) | Request IP |
| `user_agent` | text (nullable) | Request user agent |
| `tenant_id` | bigint (nullable) | Tenant ID for multi-tenancy |
| `created_at` | timestamp | When the log was created |
| `updated_at` | timestamp | When the log was updated |

### Indexes

The migration includes indexes for efficient querying:

- `action_logs_subject_index` on `(subject_type, subject_id)`
- `action_logs_actor_index` on `(actor_type, actor_id)`
- `action_logs_event_index` on `event`
- `action_logs_tenant_id_index` on `tenant_id`
- `action_logs_created_at_index` on `created_at`

## Initial Configuration

After installation, review `config/action-logger.php`:

### 1. Enable/Disable Logging

```php
'enabled' => env('ACTION_LOGGER_ENABLED', true),
```

### 2. Configure Events to Log

```php
'events' => [
    'created',
    'updated',
    'deleted',
    'restored',
],
```

### 3. Ignore Sensitive Attributes

```php
'ignore_attributes' => [
    'updated_at',
    'remember_token',
    'password', // Add this!
    'api_token', // Add this!
],
```

### 4. Multi-Tenancy (if needed)

```php
'tenant' => [
    'mode' => 'column', // or 'none', 'resolver'
    'column' => 'tenant_id',
],
```

## Verify Installation

### Check Configuration

```bash
php artisan config:show action-logger
```

### Check Database

```bash
php artisan migrate:status
```

Look for `2024_01_01_000001_create_action_logs_table`.

### Test Logging

```php
// In tinker or a test
$post = Post::create(['title' => 'Test']);

// Check if log was created
ActionLog::latest()->first();
```

## Optional: Enable UI

To view logs in a web interface:

### 1. Enable in .env

```bash
ACTION_LOGGER_UI_ENABLED=true
```

### 2. Access the Dashboard

Visit: `http://your-app.test/action-logs`

### 3. (Optional) Publish UI Components

```bash
php artisan vendor:publish --tag=action-logger-ui
```

This copies Vue components to your project for customization.

See [UI Guide](./UI.md) for full documentation.

## Upgrading

### From Pre-1.0 Versions

If upgrading from a development version:

1. Check your config file against the new defaults
2. Run any new migrations
3. Clear config cache: `php artisan config:clear`

### Future Upgrades

```bash
composer update enadstack/laravel-action-logger
php artisan migrate
```

## Troubleshooting

### "Class not found" Errors

Clear the autoload cache:

```bash
composer dump-autoload
```

### Configuration Not Loading

Clear config cache:

```bash
php artisan config:clear
php artisan cache:clear
```

### Migration Already Exists

Use force flag to overwrite:

```bash
php artisan action-logger:install --force
```

### Permission Errors

Ensure directories are writable:

```bash
chmod -R 755 config/
chmod -R 755 database/migrations/
```

### Package Not Auto-Discovered

Manually register the service provider in `config/app.php`:

```php
'providers' => [
    // ...
    Enadstack\ActionLogger\ActionLoggerServiceProvider::class,
],
```

## Next Steps

After installation:

1. 📖 Read the [Quick Start](../README.md#-quick-start)
2. ⚙️ Review [Configuration Options](./CONFIGURATION.md)
3. 🔧 Check out the [API Reference](./API.md)
4. 🏢 Set up [Multi-Tenancy](./MULTI-TENANCY.md) if needed
5. 🎨 Enable the [UI Dashboard](./UI.md)

## Uninstallation

If you need to remove the package:

```bash
# Remove migration (if run)
php artisan migrate:rollback

# Remove config file
rm config/action-logger.php

# Remove UI files (if published)
rm -rf resources/js/Pages/ActionLogger
rm -rf resources/js/Components/ActionLogger

# Remove package
composer remove enadstack/laravel-action-logger
```

## See Also

- [README](../README.md)
- [Configuration Reference](./CONFIGURATION.md)
- [Commands](./COMMANDS.md)
