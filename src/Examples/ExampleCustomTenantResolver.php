<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Examples;

use Enadstack\ActionLogger\Contracts\TenantResolverInterface;

/**
 * Example: Custom Tenant Resolver
 *
 * This is an example showing how to create a custom tenant resolver
 * for your specific multi-tenancy implementation.
 *
 * To use this:
 * 1. Create your own resolver class implementing TenantResolverInterface
 * 2. Update config/action-logger.php:
 *    'tenant' => [
 *        'mode' => 'resolver',
 *        'resolver' => \App\Services\YourCustomTenantResolver::class,
 *    ]
 */
class ExampleCustomTenantResolver implements TenantResolverInterface
{
    /**
     * Example: Resolve tenant from authenticated user's organization.
     *
     * @param object|null $subject The model being audited
     * @param object|null $actor The user/system performing the action
     */
    public function resolveTenantId(?object $subject, ?object $actor): int|string|null
    {
        // Example 1: Get tenant from authenticated user
        if (auth()->check() && method_exists(auth()->user(), 'getCurrentTenantId')) {
            return auth()->user()->getCurrentTenantId();
        }

        // Example 2: Get tenant from actor's relationship
        if ($actor && method_exists($actor, 'organization')) {
            return $actor->organization?->id;
        }

        // Example 3: Get tenant from subject's relationship
        if ($subject && method_exists($subject, 'tenant')) {
            return $subject->tenant?->id;
        }

        // Example 4: Get from a global tenant context (e.g., using a package like Spatie Multi-Tenancy)
        // if (class_exists(\Spatie\Multitenancy\Models\Tenant::class)) {
        //     return \Spatie\Multitenancy\Models\Tenant::current()?->id;
        // }

        return null;
    }
}

/**
 * Example: Using RequestTenantResolver with custom configuration
 *
 * You can also extend the built-in RequestTenantResolver:
 */
class ExampleApiTenantResolver extends \Enadstack\ActionLogger\Tenant\RequestTenantResolver
{
    public function __construct()
    {
        // Configure to read from API headers
        parent::__construct(
            headerKey: 'X-Organization-ID',
            routeParameter: 'organization',
            sessionKey: 'current_organization_id',
        );
    }
}

/**
 * Example: Combining multiple strategies
 */
class ExampleHybridTenantResolver implements TenantResolverInterface
{
    public function __construct(
        protected \Enadstack\ActionLogger\Tenant\ColumnTenantResolver $columnResolver,
        protected \Enadstack\ActionLogger\Tenant\RequestTenantResolver $requestResolver,
    ) {}

    /**
     * Try multiple strategies in order of preference.
     */
    public function resolveTenantId(?object $subject, ?object $actor): int|string|null
    {
        // First, try to get from request (API context)
        $tenantId = $this->requestResolver->resolveTenantId($subject, $actor);
        if ($tenantId !== null) {
            return $tenantId;
        }

        // Fall back to column resolver (model attribute)
        $tenantId = $this->columnResolver->resolveTenantId($subject, $actor);
        if ($tenantId !== null) {
            return $tenantId;
        }

        // Final fallback: get from authenticated user
        if (auth()->check() && isset(auth()->user()->tenant_id)) {
            return auth()->user()->tenant_id;
        }

        return null;
    }
}
