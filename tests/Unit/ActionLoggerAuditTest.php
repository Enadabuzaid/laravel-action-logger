<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Unit;

use Enadstack\ActionLogger\Facades\ActionLogger;
use Enadstack\ActionLogger\Models\ActionLog;
use Enadstack\ActionLogger\Tests\Support\Models\TestModel;
use Enadstack\ActionLogger\Tests\Support\Models\User;
use Enadstack\ActionLogger\Tests\TestCase;
use Illuminate\Http\Request;

class ActionLoggerAuditTest extends TestCase
{
    private function actor(): User
    {
        return User::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x']);
    }

    /** @test */
    public function it_stores_module_severity_and_dotted_event(): void
    {
        $subject = TestModel::create(['name' => 'S', 'status' => 'active']);
        ActionLog::query()->delete();

        ActionLogger::log($subject, $this->actor(), 'roles.assigned', null, ['role' => 'admin'], null, module: 'roles', severity: 'warning');

        $log = ActionLog::first();
        $this->assertSame('roles.assigned', $log->event);
        $this->assertSame('roles', $log->module);
        $this->assertSame('warning', $log->severity);
    }

    /** @test */
    public function it_defaults_to_info_and_rejects_unknown_severity(): void
    {
        $subject = TestModel::create(['name' => 'S', 'status' => 'active']);
        ActionLog::query()->delete();

        ActionLogger::log($subject, null, 'x.y');
        $this->assertSame('info', ActionLog::first()->severity);

        $this->expectException(\InvalidArgumentException::class);
        ActionLogger::log($subject, null, 'x.y', severity: 'catastrophic');
    }

    /** @test */
    public function it_allows_a_null_subject_without_calling_the_tenant_resolver(): void
    {
        config(['action-logger.tenant.mode' => 'column']);

        ActionLogger::log(null, null, 'permissions.synced', module: 'permissions');

        $log = ActionLog::first();
        $this->assertNull($log->subject_type);
        $this->assertNull($log->subject_id);
        $this->assertNull($log->tenant_id);
        $this->assertSame('System permissions.synced n/a', $log->getDescription());
    }

    /** @test */
    public function it_uses_the_request_id_header_or_one_stable_generated_id_per_request(): void
    {
        $subject = TestModel::create(['name' => 'S', 'status' => 'active']);
        ActionLog::query()->delete();

        $this->app->instance('request', Request::create('/x', 'GET', server: ['HTTP_X_REQUEST_ID' => 'req-123']));
        ActionLogger::log($subject, null, 'a.b');
        $this->assertSame('req-123', ActionLog::first()->request_id);

        ActionLog::query()->delete();
        $this->app->instance('request', Request::create('/y'));
        ActionLogger::log($subject, null, 'a.b');
        ActionLogger::log($subject, null, 'a.c');
        $ids = ActionLog::pluck('request_id')->unique();
        $this->assertCount(1, $ids);
        $this->assertNotEmpty($ids->first());
    }

    /** @test */
    public function fluent_builder_supports_module_and_severity_and_resets(): void
    {
        $subject = TestModel::create(['name' => 'S', 'status' => 'active']);
        ActionLog::query()->delete();

        ActionLogger::for($subject)->event('users.suspended')->module('users')->severity('critical')->save();
        ActionLogger::for($subject)->event('users.updated')->save();

        $this->assertSame('critical', ActionLog::where('event', 'users.suspended')->first()->severity);
        $this->assertNull(ActionLog::where('event', 'users.updated')->first()->module);
        $this->assertSame('info', ActionLog::where('event', 'users.updated')->first()->severity);
    }

    /** @test */
    public function scopes_filter_by_module_severity_and_request(): void
    {
        $subject = TestModel::create(['name' => 'S', 'status' => 'active']);
        ActionLog::query()->delete();
        ActionLogger::log($subject, null, 'roles.assigned', module: 'roles', severity: 'warning', requestId: 'r1');
        ActionLogger::log($subject, null, 'users.updated', module: 'users', requestId: 'r2');

        $this->assertSame(1, ActionLog::forModule('roles')->count());
        $this->assertSame(1, ActionLog::severity('warning')->count());
        $this->assertSame(1, ActionLog::forRequest('r2')->count());
    }
}
