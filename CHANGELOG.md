# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-10-10

### Added
- Audit columns `module`, `severity` (info|warning|critical) and `request_id` on `action_logs`, with composite indexes on `(module, created_at)` and `(actor_type, actor_id, created_at)`. Publish and run the new migration.
- Nullable subject: `ActionLogger::log(null, ...)` records subject-less events (the tenant resolver is skipped).
- Request id from the `X-Request-ID` header or a per-request generated uuid.
- Fluent `module()`, `severity()`, `requestId()` and model scopes `forModule()`, `severity()`, `forRequest()`.
- Opt-in immutability via `action-logger.immutable` (`ACTION_LOGGER_IMMUTABLE`): model update/delete throw `ImmutableLogException`.

### Changed
- No breaking changes; new `log()` parameters are trailing and optional.

### Notes
- Bulk query-builder deletes (e.g. `action-logger:prune`) bypass model events and are not blocked by immutability.
- Morph id columns remain bigint.

## [1.0.2] - 2026-07-15

### Changed
- Moved example files out of the `src/` autoload root into a plain, non-autoloaded `examples/` directory, and updated all doc references (`README.md`, `docs/TRAIT.md`, `docs/USAGE.md`) accordingly.

### Fixed
- Removed the now-dead `autoload.exclude-from-classmap` entry in `composer.json` left over from the examples move.
- Removed the redundant `version` key from `composer.json` (Packagist derives this from git tags).

### Added
- `inertiajs/inertia-laravel` listed under `suggest` in `composer.json`, since the optional bundled UI controller/routes depend on it.

## [1.0.0] - 2024-12-24

### Added

#### Core Features
- **ActionLog Model** - Eloquent model for storing action logs with polymorphic relationships
- **ActionLogger Service** - Central service for creating action logs with both direct method and fluent API
- **HasActionLogs Trait** - Automatically log CRUD operations on any Eloquent model
- **Facade & Helpers** - `ActionLogger` facade and `log_action()`, `action_logger()` helper functions

#### Multi-Tenancy Support
- **Flexible Tenant Modes**: `none`, `column`, or `resolver`
- **Built-in Resolvers**:
  - `NullTenantResolver` - No tenancy (default)
  - `ColumnTenantResolver` - Read tenant ID from model column
  - `RequestTenantResolver` - Read tenant ID from HTTP request (headers, route, session)
- **Custom Resolver Interface** - Implement `TenantResolverInterface` for custom logic

#### Query Capabilities
- **Subject Scopes**: `forSubject()`, filter logs by specific model
- **Actor Scopes**: `forActor()`, filter logs by who performed the action
- **Event Scopes**: `forEvent()`, `forEvents()`, filter by event types
- **Time Scopes**: `betweenDates()`, `recent()`, `today()`
- **Actor Type Scopes**: `systemActions()`, `userActions()`
- **Advanced Scopes**: `withAttributeChange()`, `fromIp()`, `withMeta()`, `withMetaKey()`
- **Tenant Scopes**: `forTenant()`, filter by tenant ID

#### HasActionLogs Trait Features
- Automatic logging for `created`, `updated`, `deleted`, `restored` events
- Smart diff tracking - only logs changed attributes on updates
- Configurable ignored attributes (global and per-model)
- Custom actor resolution via `getActionLogActor()` method
- Conditional logging via `shouldLogActionEvent()` method
- Rich querying: `latestActionLog()`, `firstActionLog()`, `actionLogsForEvent()`, `recentActionLogs()`
- Timeline generation: `actionTimeline()` for formatted history display
- Actor tracking: `wasCreatedBy()`, `wasLastModifiedBy()`, `getCreator()`, `getLastModifier()`

#### Artisan Commands
- `action-logger:install` - Interactive installation with guided setup
- `action-logger:prune` - Remove old logs with dry-run support
- `action-logger:stats` - Display comprehensive statistics

#### Optional UI (Inertia + Vue 3 + shadcn-vue)
- **Dashboard** - Stats, charts, and recent activity overview
- **All Logs** - Paginated, filterable log viewer
- **Module Logs** - Logs grouped by model type
- **Subject Timeline** - Complete history for a specific record
- **Customizable** - Publishable components, configurable authorization

#### Developer Experience
- Full PHP 8.2+ and Laravel 11/12 compatibility
- Comprehensive PHPDoc annotations
- Type-safe implementations
- 55+ automated tests
- Example files for common use cases

### Configuration Options
- `enabled` - Toggle logging on/off
- `tenant.mode` - Tenancy mode (none/column/resolver)
- `tenant.column` - Column name for column-based tenancy
- `tenant.resolver` - Custom resolver class or closure
- `events` - Events to log automatically
- `ignore_models` - Models to exclude from logging
- `ignore_attributes` - Attributes to exclude globally
- `ui.enabled` - Enable/disable UI routes
- `ui.route_prefix` - Custom URL prefix
- `ui.middleware` - Middleware for UI routes
- `ui.authorize` - Authorization callback or Gate name
- `ui.modules` - Configure UI modules
- `ui.per_page` - Pagination size
- `ui.date_format` - Date display format

---

## How to Read Version Numbers

- **MAJOR**: Breaking changes that require code updates
- **MINOR**: New features, backwards compatible
- **PATCH**: Bug fixes, backwards compatible

## Upgrade Guide

### From 0.x to 1.0.0

This is the first stable release. No upgrade needed.

---

## Links

- [GitHub Repository](https://github.com/enadstack/laravel-action-logger)
- [Documentation](./README.md)
- [Issues](https://github.com/enadstack/laravel-action-logger/issues)
