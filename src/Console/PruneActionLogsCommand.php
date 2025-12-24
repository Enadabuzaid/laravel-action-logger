<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Console;

use Enadstack\ActionLogger\Models\ActionLog;
use Illuminate\Console\Command;

class PruneActionLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'action-logger:prune
                            {--days=90 : Number of days to keep}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--tenant= : Prune logs for a specific tenant only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old action logs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $tenantId = $this->option('tenant');

        $this->info("🗑️  Pruning action logs older than {$days} days...");
        $this->newLine();

        // Build query
        $query = ActionLog::where('created_at', '<', now()->subDays($days));

        if ($tenantId !== null) {
            $query->forTenant($tenantId);
            $this->comment("   Filtering for tenant: {$tenantId}");
        }

        // Get count
        $count = $query->count();

        if ($count === 0) {
            $this->info('   No logs to prune.');

            return self::SUCCESS;
        }

        // Show what will be deleted
        $this->warn("   Found {$count} log(s) to delete.");
        $this->newLine();

        if ($dryRun) {
            $this->info('   🔍 Dry run mode - no logs will be deleted.');
            $this->displaySampleLogs($query);

            return self::SUCCESS;
        }

        // Confirm deletion
        if (!$this->confirm('Are you sure you want to delete these logs?', false)) {
            $this->comment('   Operation cancelled.');

            return self::SUCCESS;
        }

        // Delete logs
        $deleted = $query->delete();

        $this->info("   ✅ Successfully deleted {$deleted} log(s).");

        return self::SUCCESS;
    }

    /**
     * Display sample logs that will be deleted.
     */
    protected function displaySampleLogs($query): void
    {
        $this->info('   Sample logs (first 5):');
        $this->newLine();

        $samples = $query->limit(5)->get();

        $headers = ['ID', 'Event', 'Subject', 'Created At'];
        $rows = $samples->map(function ($log) {
            return [
                $log->id,
                $log->event,
                class_basename($log->subject_type).' #'.$log->subject_id,
                $log->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        $this->table($headers, $rows);
    }
}
