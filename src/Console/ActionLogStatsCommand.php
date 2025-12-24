<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Console;

use Enadstack\ActionLogger\Models\ActionLog;
use Illuminate\Console\Command;

class ActionLogStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'action-logger:stats
                            {--days=30 : Number of days to analyze}
                            {--tenant= : Show stats for a specific tenant only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display action logger statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $tenantId = $this->option('tenant');

        $this->info("📊 Action Logger Statistics (Last {$days} days)");
        $this->newLine();

        // Build base query
        $query = ActionLog::where('created_at', '>=', now()->subDays($days));

        if ($tenantId !== null) {
            $query->forTenant($tenantId);
            $this->comment("Tenant: {$tenantId}");
            $this->newLine();
        }

        // Total logs
        $total = $query->count();
        $this->info("Total Logs: {$total}");
        $this->newLine();

        if ($total === 0) {
            $this->comment('No logs found in the specified period.');

            return self::SUCCESS;
        }

        // Logs by event
        $this->displayEventStats($query);

        // Logs by day
        $this->displayDailyStats($query, $days);

        // Top actors
        $this->displayTopActors($query);

        // Top subjects
        $this->displayTopSubjects($query);

        // System vs User actions
        $this->displayActionTypeStats($query);

        return self::SUCCESS;
    }

    /**
     * Display statistics by event type.
     */
    protected function displayEventStats($query): void
    {
        $this->info('📌 Logs by Event:');

        $stats = (clone $query)
            ->selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->orderByDesc('count')
            ->get();

        $headers = ['Event', 'Count', 'Percentage'];
        $total = $stats->sum('count');

        $rows = $stats->map(function ($stat) use ($total) {
            $percentage = $total > 0 ? round(($stat->count / $total) * 100, 2) : 0;

            return [
                ucfirst($stat->event),
                $stat->count,
                $percentage.'%',
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Display daily statistics.
     */
    protected function displayDailyStats($query, int $days): void
    {
        if ($days > 14) {
            return; // Skip for long periods
        }

        $this->info('📅 Daily Activity:');

        $stats = (clone $query)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(14)
            ->get();

        $headers = ['Date', 'Logs'];
        $rows = $stats->map(function ($stat) {
            return [
                $stat->date,
                $stat->count,
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Display top actors.
     */
    protected function displayTopActors($query): void
    {
        $this->info('👥 Top 10 Actors:');

        $stats = (clone $query)
            ->whereNotNull('actor_type')
            ->whereNotNull('actor_id')
            ->selectRaw('actor_type, actor_id, COUNT(*) as count')
            ->groupBy('actor_type', 'actor_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        if ($stats->isEmpty()) {
            $this->comment('   No actor data available.');
            $this->newLine();

            return;
        }

        $headers = ['Actor Type', 'Actor ID', 'Actions'];
        $rows = $stats->map(function ($stat) {
            return [
                class_basename($stat->actor_type),
                $stat->actor_id,
                $stat->count,
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Display top subjects.
     */
    protected function displayTopSubjects($query): void
    {
        $this->info('📦 Top 10 Subjects:');

        $stats = (clone $query)
            ->selectRaw('subject_type, COUNT(*) as count')
            ->groupBy('subject_type')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $headers = ['Subject Type', 'Logs'];
        $rows = $stats->map(function ($stat) {
            return [
                class_basename($stat->subject_type),
                $stat->count,
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Display system vs user action statistics.
     */
    protected function displayActionTypeStats($query): void
    {
        $this->info('🤖 Action Types:');

        $systemActions = (clone $query)->systemActions()->count();
        $userActions = (clone $query)->userActions()->count();
        $total = $systemActions + $userActions;

        $systemPercentage = $total > 0 ? round(($systemActions / $total) * 100, 2) : 0;
        $userPercentage = $total > 0 ? round(($userActions / $total) * 100, 2) : 0;

        $headers = ['Type', 'Count', 'Percentage'];
        $rows = [
            ['System Actions', $systemActions, $systemPercentage.'%'],
            ['User Actions', $userActions, $userPercentage.'%'],
        ];

        $this->table($headers, $rows);
    }
}
