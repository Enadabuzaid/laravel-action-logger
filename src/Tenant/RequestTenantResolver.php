<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tenant;

use Enadstack\ActionLogger\Contracts\TenantResolverInterface;

/**
 * Request Tenant Resolver
 *
 * This resolver attempts to extract the tenant ID from the current HTTP request.
 * Useful for APIs or applications where tenant context is passed via headers,
 * route parameters, or session.
 */
class RequestTenantResolver implements TenantResolverInterface
{
    /**
     * Create a new request tenant resolver instance.
     *
     * @param string|null $headerKey The header key to check (e.g., 'X-Tenant-ID')
     * @param string|null $routeParameter The route parameter to check (e.g., 'tenant')
     * @param string|null $sessionKey The session key to check (e.g., 'tenant_id')
     */
    public function __construct(
        protected ?string $headerKey = 'X-Tenant-ID',
        protected ?string $routeParameter = 'tenant',
        protected ?string $sessionKey = 'tenant_id',
    ) {}

    /**
     * Resolve tenant ID from the HTTP request.
     *
     * Priority:
     * 1. Check request header
     * 2. Check route parameter
     * 3. Check session
     * 4. Return null
     *
     * @param object|null $subject The model being audited
     * @param object|null $actor The user/system performing the action
     */
    public function resolveTenantId(?object $subject, ?object $actor): int|string|null
    {
        if (!app()->bound('request')) {
            return null;
        }

        $request = request();

        // Try header first
        if ($this->headerKey && $request->hasHeader($this->headerKey)) {
            $tenantId = $request->header($this->headerKey);
            if ($tenantId !== null && $tenantId !== '') {
                return is_numeric($tenantId) ? (int) $tenantId : $tenantId;
            }
        }

        // Try route parameter
        if ($this->routeParameter && $request->route($this->routeParameter)) {
            $tenantId = $request->route($this->routeParameter);
            if ($tenantId !== null && $tenantId !== '') {
                return is_numeric($tenantId) ? (int) $tenantId : $tenantId;
            }
        }

        // Try session
        if ($this->sessionKey && $request->hasSession() && $request->session()->has($this->sessionKey)) {
            $tenantId = $request->session()->get($this->sessionKey);
            if ($tenantId !== null && $tenantId !== '') {
                return is_numeric($tenantId) ? (int) $tenantId : $tenantId;
            }
        }

        return null;
    }
}
