<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Unit;

use Enadstack\ActionLogger\Models\ActionLog;
use Enadstack\ActionLogger\Tests\Support\Models\TestModel;
use Enadstack\ActionLogger\Tests\Support\Models\User;
use Enadstack\ActionLogger\Tests\TestCase;

class HasActionLogsTest extends TestCase
{
    /** @test */
    public function it_logs_created_event(): void
    {
        $model = TestModel::create([
            'name' => 'Test Model',
            'status' => 'active',
        ]);

        $this->assertDatabaseCount('action_logs', 1);

        $log = ActionLog::first();

        $this->assertEquals('created', $log->event);
        $this->assertEquals(TestModel::class, $log->subject_type);
        $this->assertEquals($model->id, $log->subject_id);
        $this->assertNull($log->old_values);
        $this->assertNotNull($log->new_values);
        $this->assertEquals('Test Model', $log->new_values['name']);
        $this->assertEquals('active', $log->new_values['status']);
    }

    /** @test */
    public function it_logs_updated_event_with_changed_attributes_only(): void
    {
        $model = TestModel::create([
            'name' => 'Original Name',
            'status' => 'active',
        ]);

        // Clear creation log
        ActionLog::query()->delete();

        $model->update(['name' => 'Updated Name']);

        $this->assertDatabaseCount('action_logs', 1);

        $log = ActionLog::first();

        $this->assertEquals('updated', $log->event);
        $this->assertEquals(['name' => 'Original Name'], $log->old_values);
        $this->assertEquals(['name' => 'Updated Name'], $log->new_values);

        // Status should not be in the diff since it didn't change
        $this->assertArrayNotHasKey('status', $log->old_values);
        $this->assertArrayNotHasKey('status', $log->new_values);
    }

    /** @test */
    public function it_logs_deleted_event(): void
    {
        $model = TestModel::create([
            'name' => 'To Be Deleted',
            'status' => 'active',
        ]);

        // Clear creation log
        ActionLog::query()->delete();

        $model->delete();

        $this->assertDatabaseCount('action_logs', 1);

        $log = ActionLog::first();

        $this->assertEquals('deleted', $log->event);
        $this->assertNotNull($log->old_values);
        $this->assertNull($log->new_values);
        $this->assertEquals('To Be Deleted', $log->old_values['name']);
    }

    /** @test */
    public function it_logs_restored_event(): void
    {
        $model = TestModel::create([
            'name' => 'To Be Restored',
            'status' => 'active',
        ]);

        $model->delete();

        // Clear previous logs
        ActionLog::query()->delete();

        $model->restore();

        // The restore event should be logged
        $this->assertDatabaseHas('action_logs', [
            'subject_type' => TestModel::class,
            'subject_id' => $model->id,
            'event' => 'restored',
        ]);

        $log = ActionLog::forEvent('restored')->first();

        $this->assertNotNull($log);
        $this->assertEquals('restored', $log->event);
        $this->assertNull($log->old_values);
        $this->assertNotNull($log->new_values);
    }

    /** @test */
    public function it_respects_ignore_models_config(): void
    {
        config(['action-logger.ignore_models' => [TestModel::class]]);

        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);
        $model->update(['name' => 'Updated']);
        $model->delete();

        $this->assertDatabaseCount('action_logs', 0);
    }

    /** @test */
    public function it_respects_ignore_attributes_config(): void
    {
        config(['action-logger.ignore_attributes' => ['status', 'updated_at']]);

        $model = TestModel::create([
            'name' => 'Test',
            'status' => 'active',
        ]);

        // Clear creation log
        ActionLog::query()->delete();

        $model->update([
            'name' => 'Updated',
            'status' => 'inactive', // Should be ignored
        ]);

        $log = ActionLog::first();

        $this->assertEquals(['name' => 'Test'], $log->old_values);
        $this->assertEquals(['name' => 'Updated'], $log->new_values);
        $this->assertArrayNotHasKey('status', $log->old_values);
        $this->assertArrayNotHasKey('status', $log->new_values);
    }

    /** @test */
    public function it_can_log_custom_action_with_log_action_method(): void
    {
        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);

        // Clear creation log
        ActionLog::query()->delete();

        $model->logAction(
            event: 'status_changed',
            old: ['status' => 'active'],
            new: ['status' => 'inactive'],
        );

        $this->assertDatabaseCount('action_logs', 1);

        $log = ActionLog::first();

        $this->assertEquals('status_changed', $log->event);
        $this->assertEquals(['status' => 'active'], $log->old_values);
        $this->assertEquals(['status' => 'inactive'], $log->new_values);
    }

    /** @test */
    public function it_provides_relationship_to_action_logs(): void
    {
        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);

        $logs = $model->actionLogs;

        $this->assertCount(1, $logs);
        $this->assertEquals('created', $logs->first()->event);
    }

    /** @test */
    public function it_provides_helper_methods(): void
    {
        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);

        // Travel forward 1 second to ensure updated is after created
        $this->travel(1)->seconds();
        $model->update(['name' => 'Updated']);
        $this->travelBack();

        $this->assertTrue($model->hasActionLogs());
        $this->assertEquals(2, $model->actionLogsCount());

        $latestLog = $model->latestActionLog();
        $this->assertEquals('updated', $latestLog->event);

        $createdLogs = $model->actionLogsForEvent('created');
        $this->assertCount(1, $createdLogs);

        $firstLog = $model->firstActionLog();
        $this->assertEquals('created', $firstLog->event);
    }

    /** @test */
    public function it_tracks_actors(): void
    {
        $user = User::create(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']);

        $this->actingAs($user);

        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);

        $log = ActionLog::first();

        $this->assertEquals(User::class, $log->actor_type);
        $this->assertEquals($user->id, $log->actor_id);
        $this->assertTrue($model->wasCreatedBy($user));
        $this->assertEquals($user->id, $model->getCreator()->id);
    }

    /** @test */
    public function it_provides_recent_logs_helper(): void
    {
        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);

        // Create some updates
        for ($i = 0; $i < 5; $i++) {
            $model->update(['name' => "Update $i"]);
        }

        $recentLogs = $model->recentActionLogs(7);

        $this->assertCount(6, $recentLogs); // 1 created + 5 updated
    }

    /** @test */
    public function it_provides_timeline_helper(): void
    {
        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);
        $model->update(['name' => 'Updated']);

        $timeline = $model->actionTimeline(10);

        $this->assertCount(2, $timeline);
        $this->assertArrayHasKey('event', $timeline->first());
        $this->assertArrayHasKey('description', $timeline->first());
        $this->assertArrayHasKey('changes', $timeline->first());
    }

    /** @test */
    public function it_tracks_modification_status(): void
    {
        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);

        $this->assertFalse($model->hasBeenModified());

        $model->update(['name' => 'Updated']);

        $this->assertTrue($model->hasBeenModified());
        $this->assertEquals(1, $model->updateCount());
    }
}
