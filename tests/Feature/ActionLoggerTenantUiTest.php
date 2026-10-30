<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Feature;

use Enadstack\ActionLogger\Facades\ActionLogger;
use Enadstack\ActionLogger\Tests\Support\Models\TestModel;
use Enadstack\ActionLogger\Tests\Support\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;

class TenantContextHolder
{
    public static int|string|null $id = null;
}

class ActionLoggerTenantUiTest extends ActionLoggerUiTest
{
    private const MODULES = ['test_models' => [
        'label' => 'Test Models',
        'subject_type' => TestModel::class,
        'icon' => 'Tag',
    ]];

    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        TenantContextHolder::$id = null;
        config(['action-logger.ui.modules' => self::MODULES]);
    }

    private function seedTenants(): void
    {
        config(['action-logger.tenant.mode' => 'resolver']);
        ActionLogger::resolveTenantUsing(fn () => TenantContextHolder::$id);

        foreach (['a' => 1, 'b' => 2, 'platform' => null] as $name => $tenant) {
            TenantContextHolder::$id = $tenant;
            $this->ids[$name] = TestModel::create(['name' => $name, 'status' => 'active'])->id;
        }
    }

    private function viewAs(int|string|null $tenant): void
    {
        $user = new User(['id' => 99, 'name' => 'Viewer']);
        $this->actingAs($user);
        TenantContextHolder::$id = $tenant;
    }

    private function subjectUrl(string $key): string
    {
        return '/action-logs/subject/'.base64_encode(TestModel::class).'/'.$this->ids[$key];
    }

    /** @test */
    public function tenant_a_sees_only_its_rows_on_every_route(): void
    {
        $this->seedTenants();
        $this->viewAs(1);

        $this->get('/action-logs')->assertOk()->assertInertia(
            fn (Assert $p) => $p->where('stats.total_logs', 1)->has('recentLogs', 1),
        );
        $this->get('/action-logs/all')->assertOk()->assertInertia(
            fn (Assert $p) => $p->has('logs.data', 1),
        );
        $this->get('/action-logs/modules/test_models')->assertOk()->assertInertia(
            fn (Assert $p) => $p->has('logs.data', 1),
        );
        $this->get($this->subjectUrl('a'))->assertOk()->assertInertia(
            fn (Assert $p) => $p->has('logs.data', 1),
        );
    }

    /** @test */
    public function tenant_a_gets_404_for_tenant_b_and_platform_subjects(): void
    {
        $this->seedTenants();
        $this->viewAs(1);

        $this->get($this->subjectUrl('b'))->assertNotFound();
        $this->get($this->subjectUrl('platform'))->assertNotFound();
    }

    /** @test */
    public function tenant_less_viewer_gets_403_on_every_route_without_the_ability(): void
    {
        $this->seedTenants();
        $this->viewAs(null);

        foreach (['/action-logs', '/action-logs/all', '/action-logs/modules/test_models', $this->subjectUrl('a')] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    /** @test */
    public function tenant_less_viewer_with_platform_ability_sees_all_rows(): void
    {
        $this->seedTenants();
        config(['action-logger.tenant.platform_ability' => 'view-platform-logs']);
        Gate::define('view-platform-logs', fn () => true);
        $this->viewAs(null);

        $this->get('/action-logs/all')->assertOk()->assertInertia(
            fn (Assert $p) => $p->has('logs.data', 3),
        );
        $this->get($this->subjectUrl('b'))->assertOk();
    }

    /** @test */
    public function mode_none_is_unchanged_including_subject_without_logs(): void
    {
        TestModel::create(['name' => 'x', 'status' => 'active']);
        TestModel::withoutEvents(fn () => TestModel::create(['name' => 'silent', 'status' => 'active']));
        $silent = TestModel::where('name', 'silent')->value('id');

        $this->get('/action-logs/all')->assertOk()->assertInertia(
            fn (Assert $p) => $p->has('logs.data', 1),
        );
        $this->get('/action-logs/subject/'.base64_encode(TestModel::class).'/'.$silent)->assertOk();
    }
}
