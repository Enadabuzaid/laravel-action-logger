<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Unit;

use Enadstack\ActionLogger\Facades\ActionLogger;
use Enadstack\ActionLogger\Models\ActionLog;
use Enadstack\ActionLogger\Tests\Support\Models\TestModel;
use Enadstack\ActionLogger\Tests\TestCase;

class TenancyTest extends TestCase
{
    /** @test */
    public function it_stores_null_tenant_id_when_mode_none(): void
    {
        config(['action-logger.tenant.mode' => 'none']);

        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);

        $log = ActionLog::first();

        $this->assertNull($log->tenant_id);
    }

    /** @test */
    public function it_stores_tenant_id_when_mode_column(): void
    {
        config([
            'action-logger.tenant.mode' => 'column',
            'action-logger.tenant.column' => 'tenant_id',
        ]);

        $model = TestModel::create([
            'name' => 'Test',
            'status' => 'active',
            'tenant_id' => 123,
        ]);

        $log = ActionLog::first();

        $this->assertEquals(123, $log->tenant_id);
    }

    /** @test */
    public function it_scopes_queries_by_tenant_id(): void
    {
        config([
            'action-logger.tenant.mode' => 'column',
            'action-logger.tenant.column' => 'tenant_id',
        ]);

        // Create logs for tenant 1
        $model1 = TestModel::create([
            'name' => 'Tenant 1 Model',
            'status' => 'active',
            'tenant_id' => 1,
        ]);

        // Create logs for tenant 2
        $model2 = TestModel::create([
            'name' => 'Tenant 2 Model',
            'status' => 'active',
            'tenant_id' => 2,
        ]);

        // Query for tenant 1 only
        $tenant1Logs = ActionLog::forTenant(1)->get();
        $this->assertCount(1, $tenant1Logs);
        $this->assertEquals(1, $tenant1Logs->first()->tenant_id);

        // Query for tenant 2 only
        $tenant2Logs = ActionLog::forTenant(2)->get();
        $this->assertCount(1, $tenant2Logs);
        $this->assertEquals(2, $tenant2Logs->first()->tenant_id);

        // Query for null tenant
        config(['action-logger.tenant.mode' => 'none']);
        $model3 = TestModel::create(['name' => 'No Tenant', 'status' => 'active']);

        $nullTenantLogs = ActionLog::forTenant(null)->get();
        $this->assertCount(1, $nullTenantLogs);
        $this->assertNull($nullTenantLogs->first()->tenant_id);
    }

    /** @test */
    public function it_uses_column_tenant_resolver(): void
    {
        config([
            'action-logger.tenant.mode' => 'column',
            'action-logger.tenant.column' => 'tenant_id',
        ]);

        $subject = TestModel::create([
            'name' => 'Test',
            'status' => 'active',
            'tenant_id' => 456,
        ]);

        // Clear creation log
        ActionLog::query()->delete();

        // Manually log to test resolver
        ActionLogger::log(
            subject: $subject,
            actor: null,
            event: 'test_event',
        );

        $log = ActionLog::first();

        $this->assertEquals(456, $log->tenant_id);
    }

    /** @test */
    public function it_handles_missing_tenant_column_gracefully(): void
    {
        config([
            'action-logger.tenant.mode' => 'column',
            'action-logger.tenant.column' => 'nonexistent_column',
        ]);

        // Should not throw an error
        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);

        $log = ActionLog::first();

        // Should default to null when column doesn't exist
        $this->assertNull($log->tenant_id);
    }

    /** @test */
    public function it_can_filter_logs_by_multiple_tenants(): void
    {
        config([
            'action-logger.tenant.mode' => 'column',
            'action-logger.tenant.column' => 'tenant_id',
        ]);

        // Create logs for different tenants
        for ($i = 1; $i <= 3; $i++) {
            TestModel::create([
                'name' => "Tenant $i Model",
                'status' => 'active',
                'tenant_id' => $i,
            ]);
        }

        $this->assertEquals(3, ActionLog::count());
        $this->assertEquals(1, ActionLog::forTenant(1)->count());
        $this->assertEquals(1, ActionLog::forTenant(2)->count());
        $this->assertEquals(1, ActionLog::forTenant(3)->count());
    }

    /** @test */
    public function it_respects_tenant_in_relationship_queries(): void
    {
        config([
            'action-logger.tenant.mode' => 'column',
            'action-logger.tenant.column' => 'tenant_id',
        ]);

        $model = TestModel::create([
            'name' => 'Test',
            'status' => 'active',
            'tenant_id' => 999,
        ]);

        $model->update(['name' => 'Updated']);

        // All logs for this model should have the same tenant_id
        $logs = $model->actionLogs;

        $this->assertCount(2, $logs);
        $this->assertTrue($logs->every(fn ($log) => $log->tenant_id === 999));
    }
}
