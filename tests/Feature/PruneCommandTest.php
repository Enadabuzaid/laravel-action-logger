<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Feature;

use Enadstack\ActionLogger\Models\ActionLog;
use Enadstack\ActionLogger\Tests\Support\Models\TestModel;
use Enadstack\ActionLogger\Tests\TestCase;

class PruneCommandTest extends TestCase
{
    /** @test */
    public function it_prunes_old_logs(): void
    {
        // Create old log using time travel
        $this->travel(-91)->days();
        $oldLog = ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'test',
        ]);
        $this->travelBack();

        // Create recent log
        $this->travel(-30)->days();
        $recentLog = ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 2,
            'event' => 'test',
        ]);
        $this->travelBack();

        $this->artisan('action-logger:prune', ['--days' => 90])
            ->expectsQuestion('Are you sure you want to delete these logs?', true)
            ->assertSuccessful();

        $this->assertDatabaseMissing('action_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('action_logs', ['id' => $recentLog->id]);
    }

    /** @test */
    public function it_shows_dry_run_preview(): void
    {
        $this->travel(-91)->days();
        ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'test',
        ]);
        $this->travelBack();

        $this->artisan('action-logger:prune', ['--days' => 90, '--dry-run' => true])
            ->expectsOutput('   🔍 Dry run mode - no logs will be deleted.')
            ->assertSuccessful();

        // Log should still exist
        $this->assertDatabaseCount('action_logs', 1);
    }

    /** @test */
    public function it_prunes_by_tenant(): void
    {
        config([
            'action-logger.tenant.mode' => 'column',
            'action-logger.tenant.column' => 'tenant_id',
        ]);

        $this->travel(-91)->days();

        // Create old logs for different tenants
        ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'test',
            'tenant_id' => 1,
        ]);

        ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 2,
            'event' => 'test',
            'tenant_id' => 2,
        ]);

        $this->travelBack();

        $this->artisan('action-logger:prune', ['--days' => 90, '--tenant' => 1])
            ->expectsQuestion('Are you sure you want to delete these logs?', true)
            ->assertSuccessful();

        // Only tenant 1 logs should be deleted
        $this->assertEquals(1, ActionLog::count());
        $this->assertEquals(2, ActionLog::first()->tenant_id);
    }
}
