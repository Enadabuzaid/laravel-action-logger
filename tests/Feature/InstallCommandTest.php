<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Feature;

use Enadstack\ActionLogger\Tests\TestCase;
use Illuminate\Support\Facades\File;

class InstallCommandTest extends TestCase
{
    /** @test */
    public function it_publishes_config_file(): void
    {
        $configPath = config_path('action-logger.php');

        // Ensure file doesn't exist
        if (File::exists($configPath)) {
            File::delete($configPath);
        }

        $this->artisan('action-logger:install', ['--no-interaction' => true])
            ->assertSuccessful();

        $this->assertTrue(File::exists($configPath));

        // Cleanup
        File::delete($configPath);
    }

    /** @test */
    public function it_displays_usage_instructions(): void
    {
        $this->artisan('action-logger:install', ['--no-interaction' => true])
            ->expectsOutput('🚀 Installing Action Logger...')
            ->assertSuccessful();
    }
}
