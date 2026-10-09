<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Unit;

use Enadstack\ActionLogger\Exceptions\ImmutableLogException;
use Enadstack\ActionLogger\Models\ActionLog;
use Enadstack\ActionLogger\Tests\TestCase;

class ActionLogImmutabilityTest extends TestCase
{
    /** @test */
    public function entries_can_be_changed_when_immutability_is_off(): void
    {
        $log = ActionLog::create(['subject_type' => 'x', 'subject_id' => 1, 'event' => 'a.b']);

        $log->update(['event' => 'changed']);
        $this->assertSame('changed', $log->fresh()->event);

        $log->delete();
        $this->assertSame(0, ActionLog::count());
    }

    /** @test */
    public function instance_update_and_delete_throw_when_immutable(): void
    {
        config(['action-logger.immutable' => true]);
        $log = ActionLog::create(['subject_type' => 'x', 'subject_id' => 1, 'event' => 'a.b']);

        try {
            $log->update(['event' => 'changed']);
            $this->fail('update should throw');
        } catch (ImmutableLogException) {
        }

        try {
            $log->delete();
            $this->fail('delete should throw');
        } catch (ImmutableLogException) {
        }

        $this->assertSame('a.b', $log->fresh()->event);
    }

    /** @test */
    public function bulk_prune_still_works_when_immutable(): void
    {
        config(['action-logger.immutable' => true]);
        $old = ActionLog::create(['subject_type' => 'x', 'subject_id' => 1, 'event' => 'a.b']);
        ActionLog::query()->whereKey($old->id)->toBase()->update(['created_at' => now()->subDays(400)]);

        $this->artisan('action-logger:prune', ['--days' => 30])
            ->expectsQuestion('Are you sure you want to delete these logs?', true)
            ->assertExitCode(0);

        $this->assertSame(0, ActionLog::count());
    }
}
