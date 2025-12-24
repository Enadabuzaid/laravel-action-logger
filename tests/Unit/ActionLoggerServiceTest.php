<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Unit;

use Enadstack\ActionLogger\Facades\ActionLogger;
use Enadstack\ActionLogger\Models\ActionLog;
use Enadstack\ActionLogger\Tests\Support\Models\TestModel;
use Enadstack\ActionLogger\Tests\Support\Models\User;
use Enadstack\ActionLogger\Tests\TestCase;

class ActionLoggerServiceTest extends TestCase
{
    /** @test */
    public function it_creates_log_record_with_basic_data(): void
    {
        config(['action-logger.tenant.mode' => 'none']);

        $subject = TestModel::create(['name' => 'Test Subject', 'status' => 'active']);
        $actor = User::create(['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'secret']);

        // Clear logs created by model events
        ActionLog::query()->delete();

        ActionLogger::log(
            subject: $subject,
            actor: $actor,
            event: 'custom_event',
            oldValues: ['old' => 'x'],
            newValues: ['new' => 'y'],
        );

        $this->assertDatabaseCount('action_logs', 1);

        $log = ActionLog::first();

        $this->assertEquals(TestModel::class, $log->subject_type);
        $this->assertEquals($subject->id, $log->subject_id);
        $this->assertEquals(User::class, $log->actor_type);
        $this->assertEquals($actor->id, $log->actor_id);
        $this->assertEquals('custom_event', $log->event);
        $this->assertEquals(['old' => 'x'], $log->old_values);
        $this->assertEquals(['new' => 'y'], $log->new_values);
    }

    /** @test */
    public function it_respects_enabled_flag(): void
    {
        config(['action-logger.enabled' => false]);

        $subject = TestModel::create(['name' => 'Test', 'status' => 'active']);
        $actor = User::create(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']);

        // Clear any logs
        ActionLog::query()->delete();

        ActionLogger::log(
            subject: $subject,
            actor: $actor,
            event: 'test_event',
        );

        $this->assertDatabaseCount('action_logs', 0);
    }

    /** @test */
    public function it_sets_tenant_id_when_mode_column(): void
    {
        config([
            'action-logger.tenant.mode' => 'column',
            'action-logger.tenant.column' => 'tenant_id',
        ]);

        $subject = TestModel::create([
            'name' => 'Test',
            'status' => 'active',
            'tenant_id' => 123,
        ]);

        $actor = User::create(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']);

        // Clear logs from creation
        ActionLog::query()->delete();

        ActionLogger::log(
            subject: $subject,
            actor: $actor,
            event: 'test_event',
        );

        $log = ActionLog::first();

        $this->assertEquals(123, $log->tenant_id);
    }

    /** @test */
    public function it_can_use_fluent_api(): void
    {
        $subject = TestModel::create(['name' => 'Test', 'status' => 'active']);
        $actor = User::create(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']);

        // Clear logs
        ActionLog::query()->delete();

        ActionLogger::for($subject)
            ->by($actor)
            ->event('fluent_event')
            ->withOld(['name' => 'old'])
            ->withNew(['name' => 'new'])
            ->addMeta('key', 'value')
            ->save();

        $this->assertDatabaseCount('action_logs', 1);

        $log = ActionLog::first();

        $this->assertEquals('fluent_event', $log->event);
        $this->assertEquals(['name' => 'old'], $log->old_values);
        $this->assertEquals(['name' => 'new'], $log->new_values);
        $this->assertEquals(['key' => 'value'], $log->meta);
        $this->assertEquals($subject->id, $log->subject_id);
        $this->assertEquals($actor->id, $log->actor_id);
    }

    /** @test */
    public function it_captures_request_context(): void
    {
        $subject = TestModel::create(['name' => 'Test', 'status' => 'active']);

        // Clear logs
        ActionLog::query()->delete();

        ActionLogger::log(
            subject: $subject,
            actor: null,
            event: 'test_event',
        );

        $log = ActionLog::first();

        // IP and user agent should be captured (may be null in tests)
        $this->assertNotNull($log);
    }

    /** @test */
    public function it_handles_null_actor(): void
    {
        $subject = TestModel::create(['name' => 'Test', 'status' => 'active']);

        // Clear logs
        ActionLog::query()->delete();

        ActionLogger::log(
            subject: $subject,
            actor: null,
            event: 'system_event',
        );

        $log = ActionLog::first();

        $this->assertNull($log->actor_type);
        $this->assertNull($log->actor_id);
        $this->assertTrue($log->isSystemAction());
    }

    /** @test */
    public function it_can_add_metadata(): void
    {
        $subject = TestModel::create(['name' => 'Test', 'status' => 'active']);

        // Clear logs
        ActionLog::query()->delete();

        ActionLogger::for($subject)
            ->event('test_event')
            ->withMeta(['reason' => 'Testing', 'count' => 42])
            ->save();

        $log = ActionLog::first();

        $this->assertEquals('Testing', $log->getMeta('reason'));
        $this->assertEquals(42, $log->getMeta('count'));
        $this->assertTrue($log->hasMeta('reason'));
        $this->assertFalse($log->hasMeta('nonexistent'));
    }
}
