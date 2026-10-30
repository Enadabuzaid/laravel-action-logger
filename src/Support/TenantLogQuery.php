<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Support;

use Enadstack\ActionLogger\Models\ActionLog;
use Enadstack\ActionLogger\Services\ActionLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * Builds the tenant-scoped base query used by the package UI.
 *
 * - mode "none": no filter.
 * - tenant resolved for the viewer: only that tenant's rows (never null rows).
 * - no tenant (platform actor): 403 unless `action-logger.tenant.platform_ability` passes, then all rows.
 */
class TenantLogQuery
{
    public function __construct(
        protected ActionLogger $logger,
    ) {}

    /**
     * @return Builder<ActionLog>
     */
    public function for(?Authenticatable $viewer): Builder
    {
        $query = ActionLog::query();

        if (config('action-logger.tenant.mode', 'none') === 'none') {
            return $query;
        }

        $tenantId = $this->logger->resolveTenantId(null, $viewer);

        if ($tenantId !== null) {
            return $query->forTenant($tenantId);
        }

        $ability = config('action-logger.tenant.platform_ability');

        abort_if(
            !is_string($ability) || $ability === '' || !Gate::forUser($viewer)->allows($ability),
            403,
            'Unauthorized to view platform action logs',
        );

        return $query;
    }
}
