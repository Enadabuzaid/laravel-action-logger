<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Unit;

use Enadstack\ActionLogger\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class ActionLogAuditColumnsTest extends TestCase
{
    /** @test */
    public function audit_columns_and_indexes_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('action_logs', ['module', 'severity', 'request_id']));

        $names = array_column(Schema::getIndexes('action_logs'), 'name');
        $this->assertContains('action_logs_module_created_index', $names);
        $this->assertContains('action_logs_actor_created_index', $names);
    }

    /** @test */
    public function subject_columns_are_nullable(): void
    {
        $columns = collect(Schema::getColumns('action_logs'))->keyBy('name');

        $this->assertTrue($columns['subject_type']['nullable']);
        $this->assertTrue($columns['subject_id']['nullable']);
    }
}
