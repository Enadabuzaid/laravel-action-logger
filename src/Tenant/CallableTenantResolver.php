<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tenant;

use Closure;
use Enadstack\ActionLogger\Contracts\TenantResolverInterface;
use LogicException;

/**
 * Callable Tenant Resolver
 *
 * Wraps a callable `fn (?object $subject, ?object $actor): int|string|null`.
 * Return null for platform actors that belong to no tenant.
 */
class CallableTenantResolver implements TenantResolverInterface
{
    public function __construct(
        protected ?Closure $callback = null,
    ) {}

    public function resolveTenantId(?object $subject, ?object $actor): int|string|null
    {
        if ($this->callback === null) {
            throw new LogicException(
                'action-logger.tenant.mode is "resolver" but no resolver is registered. '
                .'Set action-logger.tenant.resolver to a class or call ActionLogger::resolveTenantUsing().',
            );
        }

        return ($this->callback)($subject, $actor);
    }
}
