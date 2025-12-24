<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Feature;

use Enadstack\ActionLogger\Tests\Support\Models\TestModel;
use Enadstack\ActionLogger\Tests\TestCase;

class StatsCommandTest extends TestCase
{
    /** @test */
    public function it_displays_statistics(): void
    {
        // Create test data
        TestModel::create(['name' => 'Test 1', 'status' => 'active']);
        TestModel::create(['name' => 'Test 2', 'status' => 'active']);

        $model = TestModel::first();
        $model->update(['name' => 'Updated']);
        $model->delete();

        $this->artisan('action-logger:stats', ['--days' => 30])
            ->expectsOutput('📊 Action Logger Statistics (Last 30 days)')
            ->assertSuccessful();
    }

    /** @test */
    public function it_shows_event_distribution(): void
    {
        TestModel::create(['name' => 'Test', 'status' => 'active']);

        $this->artisan('action-logger:stats')
            ->expectsOutputToContain('📌 Logs by Event:')
            ->assertSuccessful();
    }

    /** @test */
    public function it_filters_stats_by_tenant(): void
    {
        config([
            'action-logger.tenant.mode' => 'column',
            'action-logger.tenant.column' => 'tenant_id',
        ]);

        TestModel::create(['name' => 'Test', 'status' => 'active', 'tenant_id' => 1]);
        TestModel::create(['name' => 'Test', 'status' => 'active', 'tenant_id' => 2]);

        $this->artisan('action-logger:stats', ['--tenant' => 1])
            ->expectsOutputToContain('Tenant: 1')
            ->assertSuccessful();
    }
}
