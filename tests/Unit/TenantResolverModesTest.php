<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Unit;

use Enadstack\ActionLogger\Contracts\TenantResolverInterface;
use Enadstack\ActionLogger\Facades\ActionLogger;
use Enadstack\ActionLogger\Http\Controllers\ActionLogController;
use Enadstack\ActionLogger\Models\ActionLog;
use Enadstack\ActionLogger\Support\TenantLogQuery;
use Enadstack\ActionLogger\Tests\Support\Models\TestModel;
use Enadstack\ActionLogger\Tests\Support\Models\User;
use Enadstack\ActionLogger\Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantContextStub
{
    public static int|string|null $id = null;
}

class ContextClassResolver implements TenantResolverInterface
{
    public function resolveTenantId(?object $subject, ?object $actor): int|string|null
    {
        return TenantContextStub::$id;
    }
}

class TenantResolverModesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TenantContextStub::$id = null;
    }

    private function useContextClosure(): void
    {
        config(['action-logger.tenant.mode' => 'resolver']);
        ActionLogger::resolveTenantUsing(fn ($subject, $actor) => TenantContextStub::$id);
    }

    private function seedTenants(): void
    {
        foreach ([1, 2, null] as $tenant) {
            TenantContextStub::$id = $tenant;
            TestModel::create(['name' => 'row-'.($tenant ?? 'platform'), 'status' => 'active']);
        }
        TenantContextStub::$id = null;
    }

    private function viewer(int|string|null $tenant): User
    {
        $user = new User(['id' => 99, 'name' => 'Viewer']);
        $user->tenant_id = $tenant;
        $this->actingAs($user);
        TenantContextStub::$id = $tenant;

        return $user;
    }

    /** @test */
    public function closure_resolver_stores_context_tenant_and_null_for_platform(): void
    {
        $this->useContextClosure();
        $this->seedTenants();

        $this->assertEquals([1, 2, null], ActionLog::orderBy('id')->pluck('tenant_id')->all());
    }

    /** @test */
    public function class_resolver_from_config_works(): void
    {
        config([
            'action-logger.tenant.mode' => 'resolver',
            'action-logger.tenant.resolver' => ContextClassResolver::class,
        ]);
        TenantContextStub::$id = 7;

        TestModel::create(['name' => 'x', 'status' => 'active']);

        $this->assertSame(7, (int) ActionLog::first()->tenant_id);
    }

    /** @test */
    public function resolver_mode_without_resolver_throws(): void
    {
        config(['action-logger.tenant.mode' => 'resolver']);

        $this->expectException(\LogicException::class);
        ActionLogger::log(null, null, 'ping');
    }

    /** @test */
    public function invalid_config_resolver_throws_instead_of_falling_back(): void
    {
        config([
            'action-logger.tenant.mode' => 'resolver',
            'action-logger.tenant.resolver' => fn () => 5,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        ActionLogger::log(null, null, 'ping');
    }

    /** @test */
    public function subjectless_log_keeps_its_tenant(): void
    {
        $this->useContextClosure();
        TenantContextStub::$id = 5;

        ActionLogger::log(null, null, 'exported');

        $this->assertSame(5, (int) ActionLog::first()->tenant_id);
    }

    /** @test */
    public function column_mode_subjectless_log_falls_back_to_actor_tenant(): void
    {
        config(['action-logger.tenant.mode' => 'column']);
        $actor = new User(['id' => 3]);
        $actor->tenant_id = 9;

        ActionLogger::log(null, $actor, 'exported');

        $this->assertSame(9, (int) ActionLog::first()->tenant_id);
    }

    /** @test */
    public function mode_none_ignores_registered_resolver(): void
    {
        config(['action-logger.tenant.mode' => 'none']);
        ActionLogger::resolveTenantUsing(fn () => 42);

        ActionLogger::log(null, null, 'ping');

        $this->assertNull(ActionLog::first()->tenant_id);
    }

    /** @test */
    public function tenant_viewer_never_sees_other_tenants_or_platform_rows(): void
    {
        $this->useContextClosure();
        $this->seedTenants();

        $this->viewer(1);
        $names = app(TenantLogQuery::class)->for(auth()->user())->get()->pluck('new_values.name')->all();

        $this->assertSame(['row-1'], $names);
    }

    /** @test */
    public function platform_viewer_gets_403_without_ability_and_all_rows_with_it(): void
    {
        $this->useContextClosure();
        $this->seedTenants();
        $user = $this->viewer(null);

        try {
            app(TenantLogQuery::class)->for($user);
            $this->fail('Expected 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        config(['action-logger.tenant.platform_ability' => 'view-platform-logs']);
        Gate::define('view-platform-logs', fn () => true);

        $this->assertSame(3, app(TenantLogQuery::class)->for($user)->count());
    }

    /** @test */
    public function mode_none_viewer_sees_everything(): void
    {
        TestModel::create(['name' => 'a', 'status' => 'active']);

        $this->assertSame(1, app(TenantLogQuery::class)->for(null)->count());
    }

    /** @test */
    public function subject_page_returns_404_for_another_tenants_record(): void
    {
        $this->useContextClosure();
        TenantContextStub::$id = 2;
        $other = TestModel::create(['name' => 'secret', 'status' => 'active']);

        $this->viewer(1);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        (new ActionLogController)->subject(TestModel::class, $other->id);
    }
}
