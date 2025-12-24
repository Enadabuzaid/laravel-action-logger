<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Unit;

use Enadstack\ActionLogger\Models\ActionLog;
use Enadstack\ActionLogger\Tests\Support\Models\TestModel;
use Enadstack\ActionLogger\Tests\Support\Models\User;
use Enadstack\ActionLogger\Tests\TestCase;

class ActionLogModelTest extends TestCase
{
    /** @test */
    public function it_has_correct_casts(): void
    {
        $log = ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'test',
            'old_values' => ['name' => 'old'],
            'new_values' => ['name' => 'new'],
            'meta' => ['key' => 'value'],
        ]);

        $this->assertIsArray($log->old_values);
        $this->assertIsArray($log->new_values);
        $this->assertIsArray($log->meta);
    }

    /** @test */
    public function it_provides_event_type_checks(): void
    {
        $createdLog = ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'created',
        ]);

        $this->assertTrue($createdLog->isCreated());
        $this->assertFalse($createdLog->isUpdated());
        $this->assertFalse($createdLog->isDeleted());
        $this->assertFalse($createdLog->isRestored());

        $updatedLog = ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'updated',
        ]);

        $this->assertTrue($updatedLog->isUpdated());
    }

    /** @test */
    public function it_provides_description(): void
    {
        $user = User::create(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']);

        $log = ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 123,
            'actor_type' => User::class,
            'actor_id' => $user->id,
            'event' => 'updated',
        ]);

        $description = $log->getDescription();

        $this->assertStringContainsString('User', $description);
        $this->assertStringContainsString('updated', $description);
        $this->assertStringContainsString('TestModel', $description);
        $this->assertStringContainsString('123', $description);
    }

    /** @test */
    public function it_calculates_changes(): void
    {
        $log = ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'updated',
            'old_values' => ['name' => 'Old Name', 'status' => 'active'],
            'new_values' => ['name' => 'New Name', 'status' => 'active'],
        ]);

        $changes = $log->getChanges();

        $this->assertArrayHasKey('name', $changes);
        $this->assertEquals('Old Name', $changes['name']['old']);
        $this->assertEquals('New Name', $changes['name']['new']);

        // Status didn't change, so it shouldn't be in changes
        $this->assertArrayNotHasKey('status', $changes);
    }

    /** @test */
    public function it_provides_event_color(): void
    {
        $log = ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'created',
        ]);

        $this->assertEquals('green', $log->getEventColor());

        $log->event = 'updated';
        $this->assertEquals('blue', $log->getEventColor());

        $log->event = 'deleted';
        $this->assertEquals('red', $log->getEventColor());

        $log->event = 'restored';
        $this->assertEquals('yellow', $log->getEventColor());

        $log->event = 'custom';
        $this->assertEquals('gray', $log->getEventColor());
    }

    /** @test */
    public function it_provides_event_icon(): void
    {
        $log = ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'created',
        ]);

        $this->assertEquals('plus-circle', $log->getEventIcon());

        $log->event = 'updated';
        $this->assertEquals('edit', $log->getEventIcon());

        $log->event = 'deleted';
        $this->assertEquals('trash', $log->getEventIcon());

        $log->event = 'restored';
        $this->assertEquals('refresh', $log->getEventIcon());
    }

    /** @test */
    public function it_provides_event_label(): void
    {
        $log = ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'status_changed',
        ]);

        $this->assertEquals('Status changed', $log->getEventLabel());
    }

    /** @test */
    public function it_provides_time_ago(): void
    {
        // Use Carbon travel to freeze time and ensure consistent behavior
        $this->travel(-2)->hours();

        $log = ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'created',
        ]);

        $this->travelBack();

        $timeAgo = $log->fresh()->getTimeAgo();

        $this->assertStringContainsString('2 hours ago', $timeAgo);
    }

    /** @test */
    public function it_provides_formatted_date(): void
    {
        $log = ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'created',
        ]);

        $formatted = $log->getFormattedDate('Y-m-d');

        $this->assertEquals(now()->format('Y-m-d'), $formatted);
    }

    /** @test */
    public function it_converts_to_api_array(): void
    {
        $user = User::create(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']);

        $log = ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'actor_type' => User::class,
            'actor_id' => $user->id,
            'event' => 'updated',
            'old_values' => ['name' => 'old'],
            'new_values' => ['name' => 'new'],
            'meta' => ['reason' => 'test'],
            'ip_address' => '127.0.0.1',
        ]);

        $array = $log->toApiArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('event', $array);
        $this->assertArrayHasKey('event_label', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('subject', $array);
        $this->assertArrayHasKey('actor', $array);
        $this->assertArrayHasKey('changes', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('time_ago', $array);
    }

    /** @test */
    public function it_scopes_by_event(): void
    {
        ActionLog::create(['subject_type' => TestModel::class, 'subject_id' => 1, 'event' => 'created']);
        ActionLog::create(['subject_type' => TestModel::class, 'subject_id' => 2, 'event' => 'updated']);
        ActionLog::create(['subject_type' => TestModel::class, 'subject_id' => 3, 'event' => 'deleted']);

        $createdLogs = ActionLog::forEvent('created')->get();
        $this->assertCount(1, $createdLogs);

        $updatedLogs = ActionLog::forEvent('updated')->get();
        $this->assertCount(1, $updatedLogs);
    }

    /** @test */
    public function it_scopes_by_multiple_events(): void
    {
        ActionLog::create(['subject_type' => TestModel::class, 'subject_id' => 1, 'event' => 'created']);
        ActionLog::create(['subject_type' => TestModel::class, 'subject_id' => 2, 'event' => 'updated']);
        ActionLog::create(['subject_type' => TestModel::class, 'subject_id' => 3, 'event' => 'deleted']);

        $logs = ActionLog::forEvents(['created', 'updated'])->get();
        $this->assertCount(2, $logs);
    }

    /** @test */
    public function it_scopes_by_date_range(): void
    {
        $this->travel(-10)->days();
        ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'test',
        ]);

        $this->travelBack();
        $this->travel(-5)->days();
        ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 2,
            'event' => 'test',
        ]);

        $this->travelBack();
        $this->travel(-1)->hours();
        ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 3,
            'event' => 'test',
        ]);
        $this->travelBack();

        // Query from 7 days ago to now should return 2 logs (5 days ago and 1 hour ago)
        $logs = ActionLog::betweenDates(now()->subDays(7), now())->get();
        $this->assertCount(2, $logs);
    }

    /** @test */
    public function it_scopes_recent_logs(): void
    {
        $this->travel(-10)->days();
        ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'test',
        ]);

        $this->travelBack();
        $this->travel(-3)->days();
        ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 2,
            'event' => 'test',
        ]);
        $this->travelBack();

        $recentLogs = ActionLog::recent(7)->get();
        $this->assertCount(1, $recentLogs);
    }

    /** @test */
    public function it_scopes_today_logs(): void
    {
        $this->travel(-1)->days();
        ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'test',
        ]);
        $this->travelBack();

        ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 2,
            'event' => 'test',
        ]);

        $todayLogs = ActionLog::today()->get();
        $this->assertCount(1, $todayLogs);
    }

    /** @test */
    public function it_scopes_system_and_user_actions(): void
    {
        $user = User::create(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']);

        ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 1,
            'event' => 'test',
            'actor_type' => null,
            'actor_id' => null,
        ]);

        ActionLog::create([
            'subject_type' => TestModel::class,
            'subject_id' => 2,
            'event' => 'test',
            'actor_type' => User::class,
            'actor_id' => $user->id,
        ]);

        $systemLogs = ActionLog::systemActions()->get();
        $this->assertCount(1, $systemLogs);

        $userLogs = ActionLog::userActions()->get();
        $this->assertCount(1, $userLogs);
    }
}
