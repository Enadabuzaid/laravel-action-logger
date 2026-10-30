<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ActionLoggerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package config with application config
        $this->mergeConfigFrom(
            __DIR__.'/../config/action-logger.php',
            'action-logger',
        );

        // Register bindings here (will be extended in later steps)
        $this->registerBindings();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/action-logger.php' => config_path('action-logger.php'),
        ], 'action-logger-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'action-logger-migrations');

        // Publish UI files (Vue components and pages)
        $this->publishes([
            __DIR__.'/../resources/js/Pages/ActionLogger' => resource_path('js/Pages/ActionLogger'),
            __DIR__.'/../resources/js/Components/ActionLogger' => resource_path('js/Components/ActionLogger'),
        ], 'action-logger-ui');

        // Load package migrations (for testing)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register routes
        $this->registerRoutes();

        // Register commands
        $this->registerCommands();
    }

    /**
     * Register package bindings.
     */
    protected function registerBindings(): void
    {
        // Bind the tenant resolver based on configuration
        $this->app->singleton(
            \Enadstack\ActionLogger\Contracts\TenantResolverInterface::class,
            function ($app) {
                $mode = config('action-logger.tenant.mode', 'none');

                return match ($mode) {
                    'column' => new \Enadstack\ActionLogger\Tenant\ColumnTenantResolver(
                        config('action-logger.tenant.column', 'tenant_id'),
                    ),
                    'resolver' => $this->resolveCustomTenantResolver(),
                    default => new \Enadstack\ActionLogger\Tenant\NullTenantResolver,
                };
            },
        );

        $this->app->singleton(
            \Enadstack\ActionLogger\Support\Redactor::class,
            static fn (): \Enadstack\ActionLogger\Support\Redactor => new \Enadstack\ActionLogger\Support\Redactor(
                (array) config('action-logger.redact', []),
            ),
        );

        // Bind the ActionLogger service
        $this->app->singleton(
            \Enadstack\ActionLogger\Services\ActionLogger::class,
            function ($app) {
                return new \Enadstack\ActionLogger\Services\ActionLogger(
                    $app->make(\Enadstack\ActionLogger\Contracts\TenantResolverInterface::class),
                );
            },
        );
    }

    /**
     * Resolve a custom tenant resolver from configuration.
     */
    protected function resolveCustomTenantResolver(): \Enadstack\ActionLogger\Contracts\TenantResolverInterface
    {
        $resolver = config('action-logger.tenant.resolver');

        if ($resolver === null) {
            // Expect ActionLogger::resolveTenantUsing(); throws on use when none is registered.
            return new \Enadstack\ActionLogger\Tenant\CallableTenantResolver;
        }

        if ($resolver instanceof \Enadstack\ActionLogger\Contracts\TenantResolverInterface) {
            return $resolver;
        }

        if (is_string($resolver) && class_exists($resolver)) {
            $instance = $this->app->make($resolver);
        } elseif (is_callable($resolver)) {
            // Factory form (backward compatible): fn ($app) => new SomeResolver
            $instance = $resolver($this->app);
        } else {
            $instance = null;
        }

        if (!$instance instanceof \Enadstack\ActionLogger\Contracts\TenantResolverInterface) {
            throw new \InvalidArgumentException(
                'action-logger.tenant.resolver must be a TenantResolverInterface class or a factory returning one. '
                .'To resolve a tenant id from a closure use ActionLogger::resolveTenantUsing().',
            );
        }

        return $instance;
    }

    /**
     * Register package routes.
     */
    protected function registerRoutes(): void
    {
        if (config('action-logger.ui.enabled', false)) {
            Route::group([
                'prefix' => config('action-logger.ui.route_prefix', 'action-logs'),
                'middleware' => config('action-logger.ui.middleware', ['web', 'auth']),
            ], function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/action-logger.php');
            });
        }
    }

    /**
     * Register package commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Enadstack\ActionLogger\Console\InstallActionLoggerCommand::class,
                \Enadstack\ActionLogger\Console\PruneActionLogsCommand::class,
                \Enadstack\ActionLogger\Console\ActionLogStatsCommand::class,
            ]);
        }
    }
}
