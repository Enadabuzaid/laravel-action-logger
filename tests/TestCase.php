<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests;

use Enadstack\ActionLogger\ActionLoggerServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            ActionLoggerServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure action logger defaults
        $app['config']->set('action-logger.enabled', true);
        $app['config']->set('action-logger.tenant.mode', 'none');
        $app['config']->set('action-logger.events', ['created', 'updated', 'deleted', 'restored']);
        $app['config']->set('action-logger.ignore_attributes', ['updated_at', 'remember_token']);
        $app['config']->set('action-logger.ignore_models', []);
    }

    /**
     * Set up the database.
     */
    protected function setUpDatabase(): void
    {
        // Create users table for actor testing
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Create test_models table
        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
