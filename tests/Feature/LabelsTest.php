<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Feature;

use Enadstack\ActionLogger\Facades\ActionLogger;
use Enadstack\ActionLogger\Models\ActionLog;
use Enadstack\ActionLogger\Tests\Support\Models\TestModel;
use Enadstack\ActionLogger\Tests\Support\Models\User;
use Enadstack\ActionLogger\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class LabelsTest extends TestCase
{
    private function user(string $name = 'Alice'): User
    {
        return User::create(['name' => $name, 'email' => 'a@example.test', 'password' => 'x']);
    }

    /** @test */
    public function it_stores_subject_and_actor_labels_from_name(): void
    {
        $subject = TestModel::create(['name' => 'Widget', 'status' => 'active']);
        ActionLog::query()->delete();

        ActionLogger::log($subject, $this->user(), 'x.y');

        $log = ActionLog::first();
        $this->assertSame('Widget', $log->subject_label);
        $this->assertSame('Alice', $log->actor_label);
    }

    /** @test */
    public function it_falls_back_to_email(): void
    {
        $subject = new class extends \Illuminate\Database\Eloquent\Model
        {
            protected $table = 'users';

            protected $guarded = [];
        };
        $subject->forceFill(['id' => 9, 'email' => 'only@example.test']);

        ActionLogger::log($subject, null, 'x.y');

        $this->assertSame('only@example.test', ActionLog::first()->subject_label);
    }

    /** @test */
    public function explicit_labels_win(): void
    {
        $subject = TestModel::create(['name' => 'Widget', 'status' => 'active']);
        ActionLog::query()->delete();

        ActionLogger::log($subject, $this->user(), 'x.y', subjectLabel: 'Custom', actorLabel: 'Robot');

        $log = ActionLog::first();
        $this->assertSame('Custom', $log->subject_label);
        $this->assertSame('Robot', $log->actor_label);
    }

    /** @test */
    public function long_multibyte_labels_are_truncated_to_190_characters(): void
    {
        $subject = TestModel::create(['name' => str_repeat('ع', 250), 'status' => 'active']);
        ActionLog::query()->delete();

        ActionLogger::log($subject, null, 'x.y');

        $label = ActionLog::first()->subject_label;
        $this->assertSame(190, mb_strlen($label));
        $this->assertSame(str_repeat('ع', 190), $label);
    }

    /** @test */
    public function null_subject_and_actor_give_null_labels(): void
    {
        ActionLogger::log(null, null, 'system.boot');

        $log = ActionLog::first();
        $this->assertNull($log->subject_label);
        $this->assertNull($log->actor_label);
    }

    /** @test */
    public function callers_without_the_new_parameters_still_work(): void
    {
        $subject = TestModel::create(['name' => 'W', 'status' => 'active']);
        ActionLog::query()->delete();

        $log = ActionLogger::log($subject, null, 'a.b', ['x' => 1], ['x' => 2], ['m' => 1], 'mod', 'warning', 'req-1');

        $this->assertSame('mod', $log->module);
        $this->assertSame('req-1', $log->request_id);
    }

    /** @test */
    public function the_label_columns_and_subject_index_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('action_logs', ['subject_label', 'actor_label']));
        $indexes = collect(Schema::getIndexes('action_logs'))->pluck('name')->all();
        $this->assertContains('action_logs_subject_created_index', $indexes);
    }
}
