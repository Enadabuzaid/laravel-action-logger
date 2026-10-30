## enadstack/laravel-action-logger

Audit log for Eloquent models and custom events. Tables: `action_logs` (nullable `tenant_id`, `subject_*`, `actor_*`, `event`, `old_values`, `new_values`, `module`, `severity`).

- Add `HasActionLogs` to a model to log created/updated/deleted/restored automatically; ignore noisy columns via `action-logger.ignore_attributes`.
- Custom events: `ActionLogger::log($subjectOrNull, $actor, 'exported', $old, $new, $meta, module: 'orders')`. Never put phones, tokens or request bodies in values or meta; `action-logger.redact` masks listed keys.
- Tenancy (`action-logger.tenant.mode`): `none` (default), `column`, `resolver`.
  - In `resolver` mode register the resolver from a service provider, not config (closures break `config:cache`): `ActionLogger::resolveTenantUsing(fn ($subject, $actor) => TenantContext::id());`. Return `null` for platform actors.
  - An invalid or missing resolver throws in `resolver` mode. Queued jobs must restore the tenant context first, or their logs become platform rows.
- Read logs with `ActionLog::forTenant($id)`. `forTenant(null)` returns platform rows: never pass an unchecked context id to it.
- UI (`action-logger.ui.enabled`): every query is tenant-scoped. Tenant viewers never see other tenants or `null` rows; viewers without a tenant get 403 unless `action-logger.tenant.platform_ability` (a Gate ability) passes, then they see all rows.
- `php artisan action-logger:prune --days=90 [--tenant=<id>]`: `--tenant` takes an explicit id and does not use the resolver; without it all tenants (and `null` rows) are pruned.
- The package has no exports; only the UI views are tenant-scoped.
- Tests: set `action-logger.tenant.mode` in the test and call `ActionLogger::resolveTenantUsing()` per test.
