<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Contracts;

/**
 * Interface for resolving the current tenant ID.
 *
 * Implementations of this interface determine how to identify
 * the current tenant context when logging actions.
 */
interface TenantResolverInterface
{
    /**
     * Resolve the current tenant ID.
     *
     * @param object|null $subject The model being audited
     * @param object|null $actor The user/system performing the action
     * @return int|string|null The tenant ID, or null if not in a tenant context
     */
    public function resolveTenantId(?object $subject, ?object $actor): int|string|null;
}
