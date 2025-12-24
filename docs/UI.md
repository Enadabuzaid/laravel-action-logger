# Action Logger UI

Optional Inertia + Vue 3 + shadcn-vue UI for viewing action logs.

## Installation

### 1. Enable the UI

In your `.env` file:

```env
ACTION_LOGGER_UI_ENABLED=true
ACTION_LOGGER_ROUTE_PREFIX=action-logs
```

Or in `config/action-logger.php`:

```php
'ui' => [
    'enabled' => true,
    'route_prefix' => 'action-logs',
    'middleware' => ['web', 'auth'],
],
```

### 2. Publish UI Files (Optional)

If you want to customize the Vue components:

```bash
php artisan vendor:publish --tag=action-logger-ui
```

This will copy the Vue files to:
- `resources/js/Pages/ActionLogger/`
- `resources/js/Components/ActionLogger/`

### 3. Configure Modules

Update `config/action-logger.php` to match your application's models:

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
        // Add more modules...
    ],
],
```

## Routes

Once enabled, the following routes are available:

| Route | URL | Description |
|-------|-----|-------------|
| `action-logger.dashboard` | `/action-logs` | Dashboard with stats and overview |
| `action-logger.all` | `/action-logs/all` | All logs with filtering |
| `action-logger.modules.show` | `/action-logs/modules/{key}` | Logs for a specific module |
| `action-logger.subject.show` | `/action-logs/subject/{type}/{id}` | Logs for a specific record |

## Features

### Dashboard
- **Stats Cards**: Total logs, 24h, 7 days, 30 days
- **Event Distribution**: Breakdown by event type
- **Module Cards**: Quick access to each module
- **Recent Activity**: Latest 10 logs

### All Logs
- **Filtering**: By event, module, date range
- **Pagination**: 20 logs per page (configurable)
- **Search**: Full-text search (coming soon)

### Module Logs
- **Module-Specific Stats**: Total, 24h, 7 days
- **Filtering**: By event and date range
- **Dedicated View**: Only logs for that module

### Subject Logs
- **Timeline View**: Chronological history
- **Subject Info**: ID, type, status
- **Change Viewer**: Expandable diff for each log
- **Metadata Display**: JSON metadata when available

## Components

### Layout Components
- **Layout.vue**: Main layout with sidebar
- **Sidebar.vue**: Navigation menu
- **PageHeader.vue**: Page title and subtitle

### Display Components
- **StatsCard.vue**: Statistic display card
- **EventBadge.vue**: Colored event type badge
- **ModuleCard.vue**: Module information card

### Data Components
- **LogsTable.vue**: Paginated logs table
- **LogDiffViewer.vue**: Side-by-side change viewer
- **FiltersBar.vue**: Filter controls

## Customization

### Authorization

Add custom authorization logic:

```php
// config/action-logger.php
'ui' => [
    'authorize' => function($user) {
        return $user->hasRole('admin');
    },
    // Or use a Gate name:
    // 'authorize' => 'viewActionLogs',
],
```

Then define the Gate in `AuthServiceProvider`:

```php
Gate::define('viewActionLogs', function ($user) {
    return $user->isAdmin();
});
```

### Styling

The UI uses Tailwind CSS classes. To customize:

1. Publish the UI files:
   ```bash
   php artisan vendor:publish --tag=action-logger-ui
   ```

2. Edit the Vue components in `resources/js/Pages/ActionLogger/` and `resources/js/Components/ActionLogger/`

3. Rebuild your assets:
   ```bash
   npm run build
   ```

### Per-Page Items

Change the number of logs per page:

```php
// config/action-logger.php
'ui' => [
    'per_page' => 50, // Default: 20
],
```

### Date Format

Customize the date display format:

```php
// config/action-logger.php
'ui' => [
    'date_format' => 'Y-m-d H:i:s', // Default: 'M d, Y H:i'
],
```

## Integration with Your App

### Adding to Navigation

Add a link to your main navigation:

```vue
<Link :href="route('action-logger.dashboard')">
  Action Logs
</Link>
```

### Embedding in Your Layout

The Action Logger UI uses its own layout. To use your app's layout:

1. Publish the UI files
2. Edit `resources/js/Pages/ActionLogger/Layout.vue`
3. Replace with your app's layout component

Example:

```vue
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Sidebar from '@/Components/ActionLogger/Sidebar.vue'
</script>

<template>
  <AppLayout>
    <div class="flex">
      <Sidebar :modules="modules" />
      <main class="flex-1">
        <slot />
      </main>
    </div>
  </AppLayout>
</template>
```

## API Endpoints

The controller provides formatted data for all pages. You can also use these endpoints for custom integrations:

### Dashboard Data
```php
GET /action-logs
Returns: stats, modules, recentLogs
```

### All Logs
```php
GET /action-logs/all?event=updated&from=2024-01-01
Returns: logs (paginated), filters, modules, events
```

### Module Logs
```php
GET /action-logs/modules/posts?event=created
Returns: module, moduleStats, logs, filters, events
```

### Subject Logs
```php
GET /action-logs/subject/App\Models\Post/123
Returns: subject, logs
```

## Troubleshooting

### Routes Not Found

1. Ensure UI is enabled:
   ```php
   config('action-logger.ui.enabled') // should return true
   ```

2. Clear config cache:
   ```bash
   php artisan config:clear
   ```

3. Check route list:
   ```bash
   php artisan route:list | grep action-logger
   ```

### Components Not Rendering

1. Ensure Inertia is properly installed in your app
2. Check that Vue 3 is configured
3. Verify the components are in the correct directory
4. Rebuild assets: `npm run build`

### Authorization Errors

If you get 403 errors:

1. Check your authorization callback in config
2. Ensure the authenticated user has the required permissions
3. Temporarily set `'authorize' => null` to test

### Module Not Found

If a module shows "Module not found":

1. Verify the module key exists in `config/action-logger.php`
2. Check that the `subject_type` class exists
3. Clear config cache: `php artisan config:clear`

## Performance Tips

1. **Index Your Database**: The migration includes indexes, but ensure they're applied
2. **Pagination**: Use reasonable `per_page` values (20-50)
3. **Eager Loading**: The controller eager loads `subject` and `actor` relationships
4. **Caching**: Consider caching dashboard stats for high-traffic sites

## Security

1. **Always use authentication**: The default middleware includes `auth`
2. **Add authorization**: Use the `authorize` config option
3. **Validate inputs**: The controller validates all user inputs
4. **Sanitize output**: Vue automatically escapes HTML
5. **Rate limiting**: Consider adding rate limiting middleware

## Examples

### Custom Module

```php
// config/action-logger.php
'modules' => [
    'orders' => [
        'label' => 'Order Logs',
        'description' => 'Track all order changes and status updates.',
        'subject_type' => 'App\\Models\\Order',
        'icon' => 'ShoppingCart',
    ],
],
```

### Custom Authorization

```php
// config/action-logger.php
'ui' => [
    'authorize' => function($user) {
        // Only admins and managers
        return $user->hasAnyRole(['admin', 'manager']);
    },
],
```

### Custom Middleware

```php
// config/action-logger.php
'ui' => [
    'middleware' => ['web', 'auth', 'verified', 'role:admin'],
],
```

## See Also

- [Main Documentation](../README.md)
- [API Reference](./API.md)
- [Commands](./COMMANDS.md)
