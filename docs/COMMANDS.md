# Artisan Commands

The Action Logger package provides several artisan commands for installation, maintenance, and analysis.

## Installation Command

### `action-logger:install`

Install the Action Logger package with guided setup.

```bash
php artisan action-logger:install
```

#### Options

- `--force` - Overwrite existing configuration and migration files
- `--migrate` - Run migrations immediately without prompting
- `--no-interaction` - Run without any prompts (useful for CI/CD)

#### Examples

```bash
# Interactive installation (recommended for first-time setup)
php artisan action-logger:install

# Force reinstall and run migrations
php artisan action-logger:install --force --migrate

# Non-interactive installation for CI/CD
php artisan action-logger:install --no-interaction --migrate
```

#### What It Does

1. **Publishes Configuration**
   - Creates `config/action-logger.php`
   - Allows customization of tenancy, events, and ignored attributes

2. **Publishes Migrations**
   - Creates migration file in `database/migrations/`
   - Adds `action_logs` table schema

3. **Runs Migrations** (optional)
   - Creates the `action_logs` table
   - Adds all necessary indexes

4. **Displays Instructions**
   - Shows how to add the trait to models
   - Provides quick start examples
   - Links to documentation

## Maintenance Commands

### `action-logger:prune`

Remove old action logs to keep your database clean.

```bash
php artisan action-logger:prune
```

#### Options

- `--days=90` - Number of days to keep (default: 90)
- `--dry-run` - Preview what would be deleted without actually deleting
- `--tenant=ID` - Prune logs for a specific tenant only

#### Examples

```bash
# Prune logs older than 90 days (default)
php artisan action-logger:prune

# Prune logs older than 30 days
php artisan action-logger:prune --days=30

# Preview what would be deleted
php artisan action-logger:prune --days=60 --dry-run

# Prune logs for a specific tenant
php artisan action-logger:prune --tenant=1 --days=90

# Prune logs older than 1 year
php artisan action-logger:prune --days=365
```

#### Features

- **Confirmation Prompt**: Asks for confirmation before deleting
- **Dry Run Mode**: Preview deletions without making changes
- **Tenant Filtering**: Prune logs for specific tenants
- **Sample Display**: Shows first 5 logs that will be deleted
- **Count Display**: Shows total number of logs to be deleted

#### Scheduling

Add to your `app/Console/Kernel.php` to run automatically:

```php
protected function schedule(Schedule $schedule): void
{
    // Prune logs older than 90 days every week
    $schedule->command('action-logger:prune --days=90 --no-interaction')
        ->weekly()
        ->sundays()
        ->at('02:00');
}
```

## Analytics Command

### `action-logger:stats`

Display comprehensive statistics about your action logs.

```bash
php artisan action-logger:stats
```

#### Options

- `--days=30` - Number of days to analyze (default: 30)
- `--tenant=ID` - Show stats for a specific tenant only

#### Examples

```bash
# Show stats for the last 30 days (default)
php artisan action-logger:stats

# Show stats for the last 7 days
php artisan action-logger:stats --days=7

# Show stats for the last 90 days
php artisan action-logger:stats --days=90

# Show stats for a specific tenant
php artisan action-logger:stats --tenant=1

# Combine options
php artisan action-logger:stats --days=14 --tenant=2
```

#### Output Includes

1. **Total Logs**
   - Total number of logs in the period

2. **Logs by Event**
   - Breakdown by event type (created, updated, deleted, restored)
   - Count and percentage for each event

3. **Daily Activity** (for periods ≤ 14 days)
   - Logs per day
   - Helps identify activity patterns

4. **Top 10 Actors**
   - Most active users/actors
   - Number of actions per actor

5. **Top 10 Subjects**
   - Most frequently logged model types
   - Total logs per model type

6. **Action Types**
   - System actions vs user actions
   - Count and percentage breakdown

#### Example Output

```
📊 Action Logger Statistics (Last 30 days)

Total Logs: 1,247

📌 Logs by Event:
┌─────────┬───────┬────────────┐
│ Event   │ Count │ Percentage │
├─────────┼───────┼────────────┤
│ Updated │ 542   │ 43.46%     │
│ Created │ 398   │ 31.91%     │
│ Deleted │ 207   │ 16.60%     │
│ Restored│ 100   │ 8.02%      │
└─────────┴───────┴────────────┘

👥 Top 10 Actors:
┌────────────┬──────────┬─────────┐
│ Actor Type │ Actor ID │ Actions │
├────────────┼──────────┼─────────┤
│ User       │ 1        │ 342     │
│ User       │ 5        │ 198     │
│ User       │ 12       │ 156     │
└────────────┴──────────┴─────────┘

📦 Top 10 Subjects:
┌──────────────┬──────┐
│ Subject Type │ Logs │
├──────────────┼──────┤
│ Post         │ 645  │
│ Comment      │ 312  │
│ User         │ 290  │
└──────────────┴──────┘

🤖 Action Types:
┌────────────────┬───────┬────────────┐
│ Type           │ Count │ Percentage │
├────────────────┼───────┼────────────┤
│ System Actions │ 123   │ 9.86%      │
│ User Actions   │ 1124  │ 90.14%     │
└────────────────┴───────┴────────────┘
```

## Command Usage Tips

### 1. Regular Maintenance

Set up a weekly prune job:

```php
// app/Console/Kernel.php
$schedule->command('action-logger:prune --days=90')
    ->weekly();
```

### 2. Monitor Activity

Check stats regularly to understand usage patterns:

```bash
# Weekly review
php artisan action-logger:stats --days=7

# Monthly review
php artisan action-logger:stats --days=30
```

### 3. Tenant Management

For multi-tenant apps, manage logs per tenant:

```bash
# Prune old logs for tenant 1
php artisan action-logger:prune --tenant=1 --days=60

# View stats for tenant 2
php artisan action-logger:stats --tenant=2 --days=30
```

### 4. Database Cleanup

Before pruning in production, always test with dry-run:

```bash
# 1. Check what will be deleted
php artisan action-logger:prune --days=90 --dry-run

# 2. Review the output

# 3. Run the actual prune
php artisan action-logger:prune --days=90
```

### 5. CI/CD Integration

For automated deployments:

```bash
# In your deployment script
php artisan action-logger:install --no-interaction --migrate
```

## Troubleshooting

### Command Not Found

If commands are not available:

1. Clear config cache:
   ```bash
   php artisan config:clear
   ```

2. Ensure package is installed:
   ```bash
   composer require enadstack/laravel-action-logger
   ```

3. Check service provider is registered (auto-discovery should handle this)

### Permission Issues

If you get permission errors during installation:

```bash
# Run with sudo (not recommended)
sudo php artisan action-logger:install

# Or fix permissions
chmod -R 755 config/
chmod -R 755 database/migrations/
```

### Migration Already Exists

If migration already exists:

```bash
# Use --force to overwrite
php artisan action-logger:install --force
```

## See Also

- [Installation Guide](../README.md#installation)
- [Configuration](../README.md#configuration)
- [Quick Reference](./QUICK-REFERENCE.md)
