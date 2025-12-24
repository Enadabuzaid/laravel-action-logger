<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tenant;

use Enadstack\ActionLogger\Contracts\TenantResolverInterface;

/**
 * Null Tenant Resolver
 *
 * This resolver always returns null, effectively disabling tenant scoping.
 * Used when tenant mode is set to 'none' or as a fallback.
 */
class NullTenantResolver implements TenantResolverInterface
{
    /**
     * Always returns null (no tenant context).
     *
     * @param object|null $subject The model being audited
     * @param object|null $actor The user/system performing the action
     */
    public function resolveTenantId(?object $subject, ?object $actor): null
    {
        return null;
    }
}
