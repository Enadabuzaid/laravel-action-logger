<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tenant;

use Enadstack\ActionLogger\Contracts\TenantResolverInterface;

/**
 * Column Tenant Resolver
 *
 * This resolver attempts to extract the tenant ID from a column on the subject or actor.
 * It checks the subject first, then falls back to the actor.
 *
 * Example: If your models have a 'tenant_id' or 'provider_id' column,
 * this resolver will read that value.
 */
class ColumnTenantResolver implements TenantResolverInterface
{
    /**
     * Create a new column tenant resolver instance.
     *
     * @param string $column The column name to read tenant ID from
     */
    public function __construct(
        protected string $column = 'tenant_id',
    ) {}

    /**
     * Resolve tenant ID from the subject or actor's column.
     *
     * Priority:
     * 1. Check if subject has the tenant column
     * 2. Fall back to actor's tenant column
     * 3. Return null if neither has it
     *
     * @param object|null $subject The model being audited
     * @param object|null $actor The user/system performing the action
     */
    public function resolveTenantId(?object $subject, ?object $actor): int|string|null
    {
        // Try to get tenant ID from subject first
        if ($subject && $this->hasColumn($subject)) {
            $tenantId = $this->getColumnValue($subject);
            if ($tenantId !== null) {
                return $tenantId;
            }
        }

        // Fall back to actor
        if ($actor && $this->hasColumn($actor)) {
            return $this->getColumnValue($actor);
        }

        return null;
    }

    /**
     * Check if the object has the tenant column.
     */
    protected function hasColumn(object $object): bool
    {
        // Check if it's an Eloquent model with the attribute
        if (method_exists($object, 'hasAttribute')) {
            return $object->hasAttribute($this->column);
        }

        // Check if property exists
        if (property_exists($object, $this->column)) {
            return true;
        }

        // Check if it's accessible as array
        if (is_array($object) || $object instanceof \ArrayAccess) {
            return isset($object[$this->column]);
        }

        return false;
    }

    /**
     * Get the tenant column value from the object.
     */
    protected function getColumnValue(object $object): int|string|null
    {
        // Try getAttribute for Eloquent models
        if (method_exists($object, 'getAttribute')) {
            return $object->getAttribute($this->column);
        }

        // Try direct property access
        if (property_exists($object, $this->column)) {
            return $object->{$this->column};
        }

        // Try array access
        if (is_array($object) || $object instanceof \ArrayAccess) {
            return $object[$this->column] ?? null;
        }

        return null;
    }
}
