<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Examples;

use Enadstack\ActionLogger\Facades\ActionLogger;
use Enadstack\ActionLogger\Models\ActionLog;

/**
 * Example: Using the ActionLogger Service
 *
 * This file demonstrates the various ways to use the ActionLogger service
 * to log actions in your Laravel application.
 */
class ExampleUsage
{
    /**
     * Example 1: Direct method call
     */
    public function directUsage(): void
    {
        $post = \App\Models\Post::find(1);
        $user = auth()->user();

        // Log a simple action
        ActionLogger::log(
            subject: $post,
            actor: $user,
            event: 'viewed',
        );

        // Log with old and new values
        ActionLogger::log(
            subject: $post,
            actor: $user,
            event: 'updated',
            oldValues: ['title' => 'Old Title', 'status' => 'draft'],
            newValues: ['title' => 'New Title', 'status' => 'published'],
        );

        // Log with metadata
        ActionLogger::log(
            subject: $post,
            actor: $user,
            event: 'published',
            oldValues: null,
            newValues: null,
            meta: [
                'ip' => request()->ip(),
                'scheduled_at' => now()->addHours(2),
                'reason' => 'Content review completed',
            ],
        );
    }

    /**
     * Example 2: Fluent builder pattern
     */
    public function fluentUsage(): void
    {
        $post = \App\Models\Post::find(1);
        $user = auth()->user();

        // Simple fluent usage
        ActionLogger::for($post)
            ->by($user)
            ->event('deleted')
            ->save();

        // Fluent with old/new values
        ActionLogger::for($post)
            ->by($user)
            ->event('updated')
            ->withOld(['title' => 'Old Title'])
            ->withNew(['title' => 'New Title'])
            ->save();

        // Fluent with metadata
        ActionLogger::for($post)
            ->by($user)
            ->event('restored')
            ->withMeta(['reason' => 'Accidental deletion'])
            ->save();

        // Fluent with individual meta items
        ActionLogger::for($post)
            ->by($user)
            ->event('exported')
            ->addMeta('format', 'pdf')
            ->addMeta('size', '2.5MB')
            ->addMeta('pages', 45)
            ->save();
    }

    /**
     * Example 3: Using the helper function
     */
    public function helperUsage(): void
    {
        $post = \App\Models\Post::find(1);
        $user = auth()->user();

        // Direct helper
        log_action($post, $user, 'viewed');

        // Helper with values
        log_action(
            subject: $post,
            actor: $user,
            event: 'updated',
            oldValues: ['status' => 'draft'],
            newValues: ['status' => 'published'],
        );

        // Get the service instance
        $logger = action_logger();
        $logger->for($post)->by($user)->event('archived')->save();
    }

    /**
     * Example 4: System actions (no actor)
     */
    public function systemActions(): void
    {
        $post = \App\Models\Post::find(1);

        // Automated cleanup
        ActionLogger::log(
            subject: $post,
            actor: null, // System action
            event: 'auto_archived',
            meta: ['reason' => 'Inactive for 90 days'],
        );

        // Scheduled task
        ActionLogger::for($post)
            ->by(null) // No user
            ->event('auto_published')
            ->addMeta('scheduler', 'cron')
            ->addMeta('scheduled_time', now())
            ->save();
    }

    /**
     * Example 5: Custom events
     */
    public function customEvents(): void
    {
        $post = \App\Models\Post::find(1);
        $user = auth()->user();

        // Custom business events
        ActionLogger::log($post, $user, 'approved_by_editor');
        ActionLogger::log($post, $user, 'flagged_for_review');
        ActionLogger::log($post, $user, 'shared_on_social_media');
        ActionLogger::log($post, $user, 'featured_on_homepage');

        // With context
        ActionLogger::for($post)
            ->by($user)
            ->event('sent_for_translation')
            ->addMeta('target_language', 'es')
            ->addMeta('translator_id', 42)
            ->addMeta('deadline', '2024-12-31')
            ->save();
    }

    /**
     * Example 6: Querying logs
     */
    public function queryingLogs(): void
    {
        $post = \App\Models\Post::find(1);
        $user = auth()->user();

        // Get all logs for a post
        $logs = ActionLog::forSubject($post)->get();

        // Get logs by a specific user
        $userLogs = ActionLog::forActor($user)->get();

        // Get specific event type
        $deletions = ActionLog::forEvent('deleted')->get();

        // Combine scopes
        $postUpdates = ActionLog::forSubject($post)
            ->forEvent('updated')
            ->latest()
            ->get();

        // Date range
        $recentLogs = ActionLog::forSubject($post)
            ->betweenDates(now()->subDays(7), now())
            ->get();

        // With tenant filtering
        $tenantLogs = ActionLog::forTenant(auth()->user()->tenant_id)
            ->forEvent('deleted')
            ->get();

        // Get changes from a log
        foreach ($logs as $log) {
            if ($log->isUpdated()) {
                $changes = $log->getChanges();
                // ['title' => ['old' => 'Old', 'new' => 'New']]
            }
        }
    }

    /**
     * Example 7: In a controller
     */
    public function controllerExample(): void
    {
        // In your controller method
        $post = \App\Models\Post::find(1);
        $post->update(['title' => 'New Title']);

        // Log the manual update
        ActionLogger::for($post)
            ->by(auth()->user())
            ->event('manually_updated')
            ->withOld(['title' => $post->getOriginal('title')])
            ->withNew(['title' => $post->title])
            ->addMeta('source', 'admin_panel')
            ->save();
    }

    /**
     * Example 8: In a service class
     */
    public function serviceExample(): void
    {
        // In your service class
        $post = \App\Models\Post::find(1);

        // Perform business logic
        $result = $this->publishPost($post);

        // Log the result
        if ($result) {
            ActionLogger::for($post)
                ->by(auth()->user())
                ->event('published')
                ->withNew(['status' => 'published', 'published_at' => now()])
                ->addMeta('notification_sent', true)
                ->addMeta('subscribers_notified', 1250)
                ->save();
        }
    }

    /**
     * Example 9: Conditional logging
     */
    public function conditionalLogging(): void
    {
        $post = \App\Models\Post::find(1);
        $user = auth()->user();

        // Only log for certain conditions
        if ($user->isAdmin()) {
            ActionLogger::log($post, $user, 'admin_override');
        }

        // Log different events based on state
        $event = $post->wasRecentlyCreated ? 'created' : 'updated';
        ActionLogger::log($post, $user, $event);
    }

    /**
     * Example 10: Bulk operations
     */
    public function bulkOperations(): void
    {
        $posts = \App\Models\Post::where('status', 'draft')->get();
        $user = auth()->user();

        foreach ($posts as $post) {
            $post->update(['status' => 'archived']);

            ActionLogger::for($post)
                ->by($user)
                ->event('bulk_archived')
                ->withOld(['status' => 'draft'])
                ->withNew(['status' => 'archived'])
                ->addMeta('bulk_operation_id', 'BO-'.now()->timestamp)
                ->save();
        }
    }

    /**
     * Dummy method for example
     */
    private function publishPost($post): bool
    {
        return true;
    }
}
