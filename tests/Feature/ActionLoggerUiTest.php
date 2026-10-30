<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Feature;

use Enadstack\ActionLogger\Tests\Support\Models\TestModel;
use Enadstack\ActionLogger\Tests\Support\Models\User;
use Enadstack\ActionLogger\Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
class ActionLoggerUiTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [\Inertia\ServiceProvider::class]);
    }

    /**
     * Define environment setup for UI tests.
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Set app key for encryption (needed for sessions)
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Configure session
        $app['config']->set('session.driver', 'array');

        // Enable UI for these tests - must be set before service provider boots
        $app['config']->set('action-logger.ui.enabled', true);
        $app['config']->set('action-logger.ui.route_prefix', 'action-logs');
        $app['config']->set('action-logger.ui.middleware', ['web']);
        $app['config']->set('action-logger.ui.authorize', null);
        $app['config']->set('inertia.testing.ensure_pages_exist', false);

        $root = sys_get_temp_dir().'/action-logger-views';
        @mkdir($root);
        file_put_contents($root.'/app.blade.php', '<html><body>@inertia</body></html>');
        $app['config']->set('view.paths', [$root]);
    }

    /** @test */
    public function it_renders_dashboard_page(): void
    {
        // Create some test data
        $model1 = TestModel::create(['name' => 'Test 1', 'status' => 'active']);
        $model2 = TestModel::create(['name' => 'Test 2', 'status' => 'active']);
        $model1->update(['name' => 'Updated']);

        $response = $this->get('/action-logs');

        $response->assertStatus(200);

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('ActionLogger/Dashboard')
                ->has('stats')
                ->has('modules')
                ->has('recentLogs')
                ->where('stats.total_logs', 3),
        );
    }

    /** @test */
    public function it_renders_all_logs_page_with_pagination(): void
    {
        // Create 30 logs
        for ($i = 0; $i < 30; $i++) {
            TestModel::create(['name' => "Test $i", 'status' => 'active']);
        }

        $response = $this->get('/action-logs/all');

        $response->assertStatus(200);

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('ActionLogger/AllLogs')
                ->has('logs.data', 20) // Default per_page is 20
                ->has('logs.current_page')
                ->has('filters')
                ->has('modules')
                ->has('events'),
        );
    }

    /** @test */
    public function it_filters_logs_by_event(): void
    {
        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);
        $model->update(['name' => 'Updated']);
        $model->delete();

        $response = $this->get('/action-logs/all?event=updated');

        $response->assertStatus(200);

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('ActionLogger/AllLogs')
                ->where('logs.data.0.event', 'updated'),
        );
    }

    /** @test */
    public function it_renders_module_logs_page(): void
    {
        config(['action-logger.ui.modules' => [
            'test_models' => [
                'label' => 'Test Model Logs',
                'description' => 'Actions on test models',
                'subject_type' => TestModel::class,
                'icon' => 'Tag',
            ],
        ]]);

        // Create logs for TestModel
        TestModel::create(['name' => 'Test 1', 'status' => 'active']);
        TestModel::create(['name' => 'Test 2', 'status' => 'active']);

        $response = $this->get('/action-logs/modules/test_models');

        $response->assertStatus(200);

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('ActionLogger/ModuleLogs')
                ->where('module.label', 'Test Model Logs')
                ->where('module.key', 'test_models')
                ->has('moduleStats')
                ->has('logs.data', 2),
        );
    }

    /** @test */
    public function it_returns_404_for_invalid_module(): void
    {
        $response = $this->get('/action-logs/modules/nonexistent');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_renders_subject_logs_page(): void
    {
        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);
        $model->update(['name' => 'Updated']);
        $model->update(['status' => 'inactive']);

        $subjectType = base64_encode(TestModel::class);

        $response = $this->get("/action-logs/subject/{$subjectType}/{$model->id}");

        $response->assertStatus(200);

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('ActionLogger/SubjectLogs')
                ->where('subject.id', (string) $model->id)
                ->where('subject.class', TestModel::class)
                ->has('logs.data', 3), // created + 2 updates
        );
    }

    /** @test */
    public function it_shows_subject_logs_for_deleted_model(): void
    {
        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);
        $modelId = $model->id;
        $model->delete();

        $subjectType = base64_encode(TestModel::class);

        $response = $this->get("/action-logs/subject/{$subjectType}/{$modelId}");

        $response->assertStatus(200);

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('ActionLogger/SubjectLogs')
                ->where('subject.exists', false)
                ->has('logs.data', 2), // created + deleted
        );
    }

    /** @test */
    public function it_applies_authorization_callback_if_configured(): void
    {
        config(['action-logger.ui.authorize' => function ($user) {
            return false;
        }]);

        $user = User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        $response = $this->get('/action-logs');

        $response->assertStatus(403);
    }

    /** @test */
    public function it_allows_access_when_authorization_passes(): void
    {
        config(['action-logger.ui.authorize' => function ($user) {
            return true;
        }]);

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        $response = $this->get('/action-logs');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_paginates_subject_logs(): void
    {
        config(['action-logger.ui.per_page' => 5]);

        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);

        // Create multiple updates
        for ($i = 0; $i < 10; $i++) {
            $model->update(['name' => "Update $i"]);
        }

        $subjectType = base64_encode(TestModel::class);

        $response = $this->get("/action-logs/subject/{$subjectType}/{$model->id}");

        $response->assertStatus(200);

        $response->assertInertia(
            fn (Assert $page) => $page
                ->has('logs.data', 5) // Only 5 per page
                ->has('logs.current_page'),
        );
    }

    /** @test */
    public function it_includes_event_distribution_in_dashboard(): void
    {
        $model = TestModel::create(['name' => 'Test', 'status' => 'active']);
        $model->update(['name' => 'Updated']);
        $model->delete();

        $response = $this->get('/action-logs');

        $response->assertInertia(
            fn (Assert $page) => $page
                ->has('stats.counts_per_event')
                ->where('stats.counts_per_event.created', 1)
                ->where('stats.counts_per_event.updated', 1)
                ->where('stats.counts_per_event.deleted', 1),
        );
    }

    /** @test */
    public function it_shows_top_modules_in_dashboard(): void
    {
        config(['action-logger.ui.modules' => [
            'test_models' => [
                'label' => 'Test Models',
                'subject_type' => TestModel::class,
                'icon' => 'Tag',
            ],
        ]]);

        TestModel::create(['name' => 'Test 1', 'status' => 'active']);
        TestModel::create(['name' => 'Test 2', 'status' => 'active']);

        $response = $this->get('/action-logs');

        $response->assertInertia(
            fn (Assert $page) => $page
                ->has('stats.top_modules')
                ->where('stats.top_modules.0.module_key', 'test_models'),
        );
    }
}
