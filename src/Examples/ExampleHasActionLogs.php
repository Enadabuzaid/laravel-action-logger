<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Examples;

use Enadstack\ActionLogger\Traits\HasActionLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Example: Basic Model with Action Logging
 */
class ExamplePost extends Model
{
    use HasActionLogs;

    protected $fillable = ['title', 'content', 'status', 'author_id'];
}

/**
 * Example: Model with SoftDeletes
 *
 * The trait automatically detects SoftDeletes and logs restore events.
 */
class ExampleArticle extends Model
{
    use HasActionLogs;
    use SoftDeletes;

    protected $fillable = ['title', 'body', 'published_at'];
}

/**
 * Example: Model with Custom Ignored Attributes
 *
 * Define model-specific attributes to ignore in addition to global config.
 */
class ExampleProduct extends Model
{
    use HasActionLogs;

    protected $fillable = ['name', 'price', 'stock', 'views', 'last_viewed_at'];

    /**
     * Attributes to ignore when logging actions.
     * These are in addition to the global ignore_attributes config.
     */
    protected array $actionLogIgnoredAttributes = [
        'views',           // Don't log view count changes
        'last_viewed_at',  // Don't log last viewed timestamp
    ];
}

/**
 * Example: Model with Custom Actor Resolution
 *
 * Override the default actor resolution to use a different user source.
 */
class ExampleOrder extends Model
{
    use HasActionLogs;

    protected $fillable = ['customer_id', 'total', 'status', 'processed_by'];

    /**
     * Custom actor resolution.
     * Use the 'processed_by' user instead of the authenticated user.
     */
    protected function getActionLogActor(): ?object
    {
        // If there's a specific user who processed this order
        if ($this->processed_by) {
            return \App\Models\User::find($this->processed_by);
        }

        // Fall back to authenticated user
        return auth()->user();
    }
}

/**
 * Example: Model with Conditional Event Logging
 *
 * Control which events should be logged based on custom logic.
 */
class ExampleComment extends Model
{
    use HasActionLogs;

    protected $fillable = ['post_id', 'user_id', 'content', 'is_spam'];

    /**
     * Determine if a specific event should be logged.
     */
    protected function shouldLogActionEvent(string $event): bool
    {
        // Don't log updates if the comment is marked as spam
        if ($event === 'updated' && $this->is_spam) {
            return false;
        }

        // Don't log creation of spam comments
        if ($event === 'created' && $this->is_spam) {
            return false;
        }

        return true;
    }
}

/**
 * Example: Using the Trait in Practice
 */
class ExampleUsageInCode
{
    public function demonstrateAutomaticLogging(): void
    {
        // Creating a post automatically logs "created" event
        $post = ExamplePost::create([
            'title' => 'My First Post',
            'content' => 'Hello World',
            'status' => 'draft',
        ]);
        // ActionLog created with event='created', new_values=[all attributes]

        // Updating automatically logs "updated" event with only changed attributes
        $post->update(['title' => 'My Updated Post']);
        // ActionLog created with event='updated',
        // old_values=['title' => 'My First Post'],
        // new_values=['title' => 'My Updated Post']

        // Deleting automatically logs "deleted" event
        $post->delete();
        // ActionLog created with event='deleted', old_values=[all attributes]
    }

    public function demonstrateSoftDeletes(): void
    {
        $article = ExampleArticle::create([
            'title' => 'Important Article',
            'body' => 'Content here',
        ]);

        // Soft delete logs "deleted" event
        $article->delete();

        // Restore logs "restored" event
        $article->restore();
        // ActionLog created with event='restored', new_values=[all attributes]
    }

    public function demonstrateManualLogging(): void
    {
        $post = ExamplePost::find(1);

        // Log a custom event
        $post->logAction('published');

        // Log with old/new values
        $post->logAction(
            event: 'featured',
            old: ['featured' => false],
            new: ['featured' => true],
        );

        // Log with metadata
        $post->logAction(
            event: 'shared_on_social',
            meta: [
                'platform' => 'twitter',
                'url' => 'https://twitter.com/...',
                'engagement' => 1250,
            ],
        );
    }

    public function demonstrateQueryingLogs(): void
    {
        $post = ExamplePost::find(1);

        // Get all logs for this post
        $allLogs = $post->actionLogs;

        // Get the latest log
        $latestLog = $post->latestActionLog();

        // Get logs for a specific event
        $updates = $post->actionLogsForEvent('updated');

        // Check if post has any logs
        if ($post->hasActionLogs()) {
            echo "This post has {$post->actionLogsCount()} logs";
        }

        // Query with additional filters
        $recentLogs = $post->actionLogs()
            ->where('created_at', '>=', now()->subDays(7))
            ->latest()
            ->get();

        // Get logs by a specific user
        $userLogs = $post->actionLogs()
            ->where('actor_id', auth()->id())
            ->get();
    }

    public function demonstrateIgnoredAttributes(): void
    {
        $product = ExampleProduct::create([
            'name' => 'Widget',
            'price' => 19.99,
            'stock' => 100,
            'views' => 0,
        ]);

        // Update views - this won't be logged
        $product->update(['views' => 50]);
        // No ActionLog created because 'views' is ignored

        // Update price - this will be logged
        $product->update(['price' => 24.99]);
        // ActionLog created with only price change
    }

    public function demonstrateCustomActor(): void
    {
        $order = ExampleOrder::create([
            'customer_id' => 1,
            'total' => 99.99,
            'status' => 'pending',
        ]);

        // Update with a specific processor
        $order->processed_by = 42; // Admin user ID
        $order->status = 'completed';
        $order->save();
        // ActionLog will have actor_id = 42 (from getActionLogActor)
    }

    public function demonstrateConditionalLogging(): void
    {
        // Regular comment - will be logged
        $comment = ExampleComment::create([
            'post_id' => 1,
            'user_id' => 1,
            'content' => 'Great post!',
            'is_spam' => false,
        ]);
        // ActionLog created

        // Spam comment - won't be logged
        $spamComment = ExampleComment::create([
            'post_id' => 1,
            'user_id' => 999,
            'content' => 'Buy cheap products...',
            'is_spam' => true,
        ]);
        // No ActionLog created (filtered by shouldLogActionEvent)
    }

    public function demonstrateRelationship(): void
    {
        $post = ExamplePost::find(1);

        // Eager load logs
        $postWithLogs = ExamplePost::with('actionLogs')->find(1);

        // Get logs with actor relationship
        $logs = $post->actionLogs()->with('actor')->get();

        foreach ($logs as $log) {
            echo "{$log->actor->name} {$log->event} this post at {$log->created_at}";
        }
    }
}

/**
 * Example: Configuration
 */
class ExampleConfiguration
{
    /**
     * In config/action-logger.php
     */
    public function globalConfiguration(): array
    {
        return [
            // Enable/disable logging globally
            'enabled' => true,

            // Events to log automatically
            'events' => ['created', 'updated', 'deleted', 'restored'],

            // Models to completely ignore
            'ignore_models' => [
                \App\Models\ActivityLog::class, // Don't log the logs!
                \App\Models\Session::class,     // Don't log sessions
            ],

            // Attributes to ignore globally
            'ignore_attributes' => [
                'updated_at',      // Usually not meaningful
                'remember_token',  // Security
                'password',        // Security
            ],
        ];
    }

    /**
     * Disable logging for a specific model
     */
    public function ignoreSpecificModel(): void
    {
        // In config/action-logger.php
        // 'ignore_models' => [
        //     \App\Models\TempData::class,
        // ],
    }

    /**
     * Log only specific events
     */
    public function logOnlySpecificEvents(): void
    {
        // In config/action-logger.php
        // 'events' => ['created', 'deleted'], // Don't log updates
    }
}
