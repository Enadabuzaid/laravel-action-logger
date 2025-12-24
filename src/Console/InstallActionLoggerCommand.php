<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallActionLoggerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'action-logger:install
                            {--force : Overwrite existing files}
                            {--migrate : Run migrations immediately}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Action Logger package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Installing Action Logger...');
        $this->newLine();

        // Step 1: Publish configuration
        $this->publishConfiguration();

        // Step 2: Publish migrations
        $this->publishMigrations();

        // Step 3: Run migrations (if requested)
        if ($this->option('migrate') || $this->confirmMigration()) {
            $this->runMigrations();
        }

        // Step 4: Display usage instructions
        $this->displayInstructions();

        $this->newLine();
        $this->info('✅ Action Logger installed successfully!');

        return self::SUCCESS;
    }

    /**
     * Publish the configuration file.
     */
    protected function publishConfiguration(): void
    {
        $this->info('📝 Publishing configuration...');

        $params = [
            '--provider' => 'Enadstack\ActionLogger\ActionLoggerServiceProvider',
            '--tag' => 'action-logger-config',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);

        $this->comment('   ✓ Configuration published to config/action-logger.php');
        $this->newLine();
    }

    /**
     * Publish the migration files.
     */
    protected function publishMigrations(): void
    {
        $this->info('📦 Publishing migrations...');

        $params = [
            '--provider' => 'Enadstack\ActionLogger\ActionLoggerServiceProvider',
            '--tag' => 'action-logger-migrations',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);

        $this->comment('   ✓ Migrations published to database/migrations/');
        $this->newLine();
    }

    /**
     * Ask user if they want to run migrations.
     */
    protected function confirmMigration(): bool
    {
        if (!$this->input->isInteractive()) {
            return false;
        }

        return $this->confirm('Would you like to run the migrations now?', true);
    }

    /**
     * Run the migrations.
     */
    protected function runMigrations(): void
    {
        $this->info('🔄 Running migrations...');

        $this->call('migrate');

        $this->comment('   ✓ Migrations completed');
        $this->newLine();
    }

    /**
     * Display usage instructions.
     */
    protected function displayInstructions(): void
    {
        $this->info('📚 Getting Started:');
        $this->newLine();

        $this->line('1️⃣  Add the trait to your models:');
        $this->newLine();
        $this->line('   <fg=cyan>use Enadstack\ActionLogger\Traits\HasActionLogs;</>');
        $this->newLine();
        $this->line('   <fg=cyan>class Post extends Model</>');
        $this->line('   <fg=cyan>{</>');
        $this->line('   <fg=cyan>    use HasActionLogs;</>');
        $this->line('   <fg=cyan>}</>');
        $this->newLine();

        $this->line('2️⃣  All CRUD operations will be automatically logged!');
        $this->newLine();

        $this->line('3️⃣  Query logs:');
        $this->newLine();
        $this->line('   <fg=cyan>$logs = $post->actionLogs;</>');
        $this->line('   <fg=cyan>$recent = $post->recentActionLogs(7);</>');
        $this->line('   <fg=cyan>$timeline = $post->actionTimeline();</>');
        $this->newLine();

        $this->line('4️⃣  Manual logging:');
        $this->newLine();
        $this->line('   <fg=cyan>ActionLogger::log($post, $user, \'published\');</>');
        $this->line('   <fg=cyan>$post->logAction(\'featured\');</>');
        $this->newLine();

        $this->line('📖 Documentation:');
        $this->line('   • Quick Reference: <fg=yellow>docs/QUICK-REFERENCE.md</>');
        $this->line('   • API Reference: <fg=yellow>docs/API.md</>');
        $this->line('   • Trait Guide: <fg=yellow>docs/TRAIT.md</>');
        $this->newLine();

        $this->line('⚙️  Configuration:');
        $this->line('   Edit <fg=yellow>config/action-logger.php</> to customize:');
        $this->line('   • Multi-tenancy mode');
        $this->line('   • Ignored attributes');
        $this->line('   • Events to log');
        $this->newLine();
    }
}
