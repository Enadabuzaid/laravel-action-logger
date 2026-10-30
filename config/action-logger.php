<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Immutable Logs
    |--------------------------------------------------------------------------
    |
    | When true, updating or deleting an ActionLog model instance throws an
    | ImmutableLogException. Bulk query-builder deletes (such as the
    | action-logger:prune command) bypass model events and still work.
    |
    */
    'immutable' => env('ACTION_LOGGER_IMMUTABLE', false),

    /*
    |--------------------------------------------------------------------------
    | Redacted keys
    |--------------------------------------------------------------------------
    |
    | Values of these keys (case-insensitive, at any depth) in old_values,
    | new_values and meta are replaced with "[redacted]" BEFORE they are stored.
    |
    */
    'redact' => [
        'password',
        'password_confirmation',
        'remember_token',
        'token',
        'token_hash',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'secret',
        'api_key',
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Logger Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether the action logger is enabled globally.
    | When disabled, no action logs will be created.
    |
    */

    'enabled' => env('ACTION_LOGGER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the action logger handles multi-tenancy.
    |
    | Supported modes:
    | - 'none': No tenant scoping (default for single-tenant apps)
    | - 'column': Automatically extract tenant_id from a column on the subject or actor
    | - 'resolver': Use a custom resolver class to determine tenant ID
    |
    | Examples:
    |
    | Column mode (reads from model attribute):
    |   'mode' => 'column',
    |   'column' => 'tenant_id', // or 'provider_id', 'organization_id', etc.
    |
    | Custom resolver (use your own logic):
    |   'mode' => 'resolver',
    |   'resolver' => \App\Services\CustomTenantResolver::class,
    |
    | Or use a closure:
    |   'resolver' => function ($app) {
    |       return new \App\Services\CustomTenantResolver();
    |   },
    |
    | Or leave 'resolver' null and register a closure from a service provider
    | (config closures break config:cache):
    |   ActionLogger::resolveTenantUsing(fn ($subject, $actor) => TenantContext::id());
    |
    */

    'tenant' => [
        'mode' => env('ACTION_LOGGER_TENANT_MODE', 'none'), // 'none', 'column', 'resolver'
        'column' => env('ACTION_LOGGER_TENANT_COLUMN', 'tenant_id'), // or 'provider_id', etc.

        /*
         | Custom tenant resolver class.
         | Must implement: Enadstack\ActionLogger\Contracts\TenantResolverInterface
         |
         | Built-in resolvers:
         | - \Enadstack\ActionLogger\Tenant\NullTenantResolver (no tenancy)
         | - \Enadstack\ActionLogger\Tenant\ColumnTenantResolver (read from column)
         | - \Enadstack\ActionLogger\Tenant\RequestTenantResolver (read from request)
         */
        'resolver' => null,

        /*
         | Optional gate ability that lets viewers with NO tenant (platform admins)
         | open the UI and see all rows in column/resolver mode. Null = they get 403.
         */
        'platform_ability' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logged Events
    |--------------------------------------------------------------------------
    |
    | Define which Eloquent events should be automatically logged when using
    | the HasActionLogs trait.
    |
    */

    'events' => [
        'created',
        'updated',
        'deleted',
        'restored',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Models
    |--------------------------------------------------------------------------
    |
    | Models listed here will not have their actions logged, even if they
    | use the HasActionLogs trait.
    |
    */

    'ignore_models' => [
        // \App\Models\SomeModel::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Attributes
    |--------------------------------------------------------------------------
    |
    | These attributes will be excluded from the old_values and new_values
    | when logging model changes. Useful for timestamps and other fields
    | that change frequently but aren't meaningful for audit purposes.
    |
    */

    'ignore_attributes' => [
        'updated_at',
        'remember_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration (Optional)
    |--------------------------------------------------------------------------
    |
    | Configure the optional Inertia + Vue UI for viewing action logs.
    | Set 'enabled' to true to activate the UI routes and pages.
    |
    */

    'ui' => [
        'enabled' => env('ACTION_LOGGER_UI_ENABLED', false),

        'route_prefix' => env('ACTION_LOGGER_ROUTE_PREFIX', 'action-logs'),

        'middleware' => ['web', 'auth'],

        /*
         | Authorization callback
         | Return true to allow access, false to deny
         | You can also use a Gate or Policy name as a string
         */
        'authorize' => null, // function($user) { return $user->isAdmin(); }

        /*
         | Modules Configuration
         |
         | Define logical modules (categories of logs) to display in the UI.
         | The host application should customize these to match their models.
         |
         | Each module should have:
         | - label: Display name
         | - description: Short description
         | - subject_type: Fully qualified class name of the model
         | - icon: Icon identifier (for UI rendering)
         */
        'modules' => [
            'categories' => [
                'label' => 'Category Logs',
                'description' => 'Actions performed on categories.',
                'subject_type' => 'App\\Models\\Category',
                'icon' => 'Tag',
            ],
            'services' => [
                'label' => 'Service Logs',
                'description' => 'Actions performed on services.',
                'subject_type' => 'App\\Models\\Service',
                'icon' => 'Briefcase',
            ],
            'users' => [
                'label' => 'User Logs',
                'description' => 'Actions performed on users.',
                'subject_type' => 'App\\Models\\User',
                'icon' => 'Users',
            ],
            // Add more modules as needed
        ],

        /*
         | Items per page for log listings
         */
        'per_page' => 20,

        /*
         | Date format for displaying timestamps
         */
        'date_format' => 'M d, Y H:i',
    ],

];
