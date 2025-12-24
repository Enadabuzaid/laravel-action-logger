<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Traits;

use Enadstack\ActionLogger\Facades\ActionLogger;
use Enadstack\ActionLogger\Models\ActionLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasActionLogs Trait
 *
 * Add this trait to any Eloquent model to automatically log actions.
 *
 * @example
 * class Post extends Model
 * {
 *     use HasActionLogs;
 * }
 *
 * @mixin Model
 */
trait HasActionLogs
{
    /**
     * Boot the HasActionLogs trait.
     */
    protected static function bootHasActionLogs(): void
    {
        // Check if this model should be ignored
        if (static::shouldIgnoreActionLogging()) {
            return;
        }

        // Hook into Eloquent events
        static::created(function (Model $model) {
            $model->logCreatedEvent();
        });

        static::updated(function (Model $model) {
            $model->logUpdatedEvent();
        });

        static::deleted(function (Model $model) {
            $model->logDeletedEvent();
        });

        // Only hook into restored if the model uses SoftDeletes
        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                $model->logRestoredEvent();
            });
        }
    }

    /**
     * Get all action logs for this model.
     */
    public function actionLogs(): MorphMany
    {
        return $this->morphMany(ActionLog::class, 'subject');
    }

    /**
     * Log a custom action manually.
     *
     * @param string $event The event name
     * @param array|null $old Old values
     * @param array|null $new New values
     * @param array|null $meta Additional metadata
     */
    public function logAction(
        string $event,
        ?array $old = null,
        ?array $new = null,
        ?array $meta = null,
    ): ?ActionLog {
        return ActionLogger::log(
            subject: $this,
            actor: $this->resolveActor(),
            event: $event,
            oldValues: $old,
            newValues: $new,
            meta: $meta,
        );
    }

    /**
     * Log the "created" event.
     */
    protected function logCreatedEvent(): void
    {
        if (!$this->shouldLogEvent('created')) {
            return;
        }

        ActionLogger::log(
            subject: $this,
            actor: $this->resolveActor(),
            event: 'created',
            oldValues: null,
            newValues: $this->getLoggableAttributes(),
        );
    }

    /**
     * Log the "updated" event.
     */
    protected function logUpdatedEvent(): void
    {
        if (!$this->shouldLogEvent('updated')) {
            return;
        }

        // Get only the changed attributes
        $changes = $this->getChangedLoggableAttributes();

        // Don't log if there are no meaningful changes
        if (empty($changes['old']) && empty($changes['new'])) {
            return;
        }

        ActionLogger::log(
            subject: $this,
            actor: $this->resolveActor(),
            event: 'updated',
            oldValues: $changes['old'],
            newValues: $changes['new'],
        );
    }

    /**
     * Log the "deleted" event.
     */
    protected function logDeletedEvent(): void
    {
        if (!$this->shouldLogEvent('deleted')) {
            return;
        }

        ActionLogger::log(
            subject: $this,
            actor: $this->resolveActor(),
            event: 'deleted',
            oldValues: $this->getLoggableAttributes(),
            newValues: null,
        );
    }

    /**
     * Log the "restored" event.
     */
    protected function logRestoredEvent(): void
    {
        if (!$this->shouldLogEvent('restored')) {
            return;
        }

        ActionLogger::log(
            subject: $this,
            actor: $this->resolveActor(),
            event: 'restored',
            oldValues: null,
            newValues: $this->getLoggableAttributes(),
        );
    }

    /**
     * Get the attributes that should be logged (excluding ignored attributes).
     */
    protected function getLoggableAttributes(): array
    {
        $attributes = $this->getAttributes();
        $ignored = $this->getIgnoredAttributes();

        return array_diff_key($attributes, array_flip($ignored));
    }

    /**
     * Get the changed attributes that should be logged.
     *
     * @return array{old: array, new: array}
     */
    protected function getChangedLoggableAttributes(): array
    {
        $dirty = $this->getDirty();
        $ignored = $this->getIgnoredAttributes();

        // Remove ignored attributes from dirty
        $dirty = array_diff_key($dirty, array_flip($ignored));

        if (empty($dirty)) {
            return ['old' => [], 'new' => []];
        }

        $old = [];
        $new = [];

        foreach ($dirty as $key => $value) {
            $old[$key] = $this->getOriginal($key);
            $new[$key] = $value;
        }

        return ['old' => $old, 'new' => $new];
    }

    /**
     * Get the list of attributes to ignore when logging.
     */
    protected function getIgnoredAttributes(): array
    {
        // Start with global config
        $ignored = config('action-logger.ignore_attributes', []);

        // Add model-specific ignored attributes if defined
        if (property_exists($this, 'actionLogIgnoredAttributes')) {
            $ignored = array_merge($ignored, $this->actionLogIgnoredAttributes);
        }

        return $ignored;
    }

    /**
     * Resolve the actor (user) who performed the action.
     */
    protected function resolveActor(): ?object
    {
        // Allow models to define their own actor resolution
        if (method_exists($this, 'getActionLogActor')) {
            return $this->getActionLogActor();
        }

        // Default: use authenticated user
        return auth()->user();
    }

    /**
     * Check if a specific event should be logged.
     */
    protected function shouldLogEvent(string $event): bool
    {
        // Check if the event is in the configured events list
        $configuredEvents = config('action-logger.events', []);

        if (!in_array($event, $configuredEvents, true)) {
            return false;
        }

        // Allow models to override event logging
        if (method_exists($this, 'shouldLogActionEvent')) {
            return $this->shouldLogActionEvent($event);
        }

        return true;
    }

    /**
     * Check if this model should be ignored from action logging.
     */
    protected static function shouldIgnoreActionLogging(): bool
    {
        $ignoredModels = config('action-logger.ignore_models', []);

        return in_array(static::class, $ignoredModels, true);
    }

    /**
     * Get the latest action log for this model.
     */
    public function latestActionLog(): ?ActionLog
    {
        return $this->actionLogs()->latest()->first();
    }

    /**
     * Get action logs for a specific event.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function actionLogsForEvent(string $event)
    {
        return $this->actionLogs()->forEvent($event)->get();
    }

    /**
     * Check if this model has any action logs.
     */
    public function hasActionLogs(): bool
    {
        return $this->actionLogs()->exists();
    }

    /**
     * Get the count of action logs for this model.
     */
    public function actionLogsCount(): int
    {
        return $this->actionLogs()->count();
    }

    /**
     * Get action logs by a specific actor.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function actionLogsByActor(object $actor)
    {
        return $this->actionLogs()->forActor($actor)->get();
    }

    /**
     * Get action logs within a date range.
     *
     * @param \Carbon\Carbon|string $from
     * @param \Carbon\Carbon|string $to
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function actionLogsBetweenDates($from, $to)
    {
        return $this->actionLogs()->betweenDates($from, $to)->get();
    }

    /**
     * Get recent action logs (last N days).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function recentActionLogs(int $days = 7)
    {
        return $this->actionLogs()
            ->where('created_at', '>=', now()->subDays($days))
            ->latest()
            ->get();
    }

    /**
     * Get paginated action logs.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginatedActionLogs(int $perPage = 15)
    {
        return $this->actionLogs()->latest()->paginate($perPage);
    }

    /**
     * Get the first action log (creation log).
     */
    public function firstActionLog(): ?ActionLog
    {
        return $this->actionLogs()->oldest()->first();
    }

    /**
     * Get action logs grouped by event type.
     *
     * @return \Illuminate\Support\Collection
     */
    public function actionLogsByEvent()
    {
        return $this->actionLogs()
            ->get()
            ->groupBy('event');
    }

    /**
     * Get a timeline of all actions (formatted for display).
     *
     * @return \Illuminate\Support\Collection
     */
    public function actionTimeline(int $limit = 50)
    {
        return $this->actionLogs()
            ->with('actor')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (ActionLog $log) {
                return [
                    'id' => $log->id,
                    'event' => $log->event,
                    'description' => $log->getDescription(),
                    'actor' => $log->actor,
                    'changes' => $log->getChanges(),
                    'meta' => $log->meta,
                    'created_at' => $log->created_at,
                    'is_created' => $log->isCreated(),
                    'is_updated' => $log->isUpdated(),
                    'is_deleted' => $log->isDeleted(),
                    'is_restored' => $log->isRestored(),
                ];
            });
    }

    /**
     * Check if this model was created by a specific actor.
     */
    public function wasCreatedBy(object $actor): bool
    {
        $creationLog = $this->actionLogs()->forEvent('created')->first();

        if (!$creationLog || !$creationLog->actor) {
            return false;
        }

        return $creationLog->actor_type === get_class($actor)
            && $creationLog->actor_id === $actor->getKey();
    }

    /**
     * Check if this model was last modified by a specific actor.
     */
    public function wasLastModifiedBy(object $actor): bool
    {
        $latestLog = $this->latestActionLog();

        if (!$latestLog || !$latestLog->actor) {
            return false;
        }

        return $latestLog->actor_type === get_class($actor)
            && $latestLog->actor_id === $actor->getKey();
    }

    /**
     * Get the actor who created this model.
     */
    public function getCreator(): ?object
    {
        $creationLog = $this->actionLogs()->forEvent('created')->first();

        return $creationLog?->actor;
    }

    /**
     * Get the actor who last modified this model.
     */
    public function getLastModifier(): ?object
    {
        return $this->latestActionLog()?->actor;
    }

    /**
     * Check if this model has been modified since creation.
     */
    public function hasBeenModified(): bool
    {
        return $this->actionLogs()
            ->whereIn('event', ['updated', 'deleted', 'restored'])
            ->exists();
    }

    /**
     * Get the number of times this model has been updated.
     */
    public function updateCount(): int
    {
        return $this->actionLogs()->forEvent('updated')->count();
    }

    /**
     * Get all unique actors who have interacted with this model.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getUniqueActors()
    {
        return $this->actionLogs()
            ->with('actor')
            ->get()
            ->pluck('actor')
            ->filter()
            ->unique(fn ($actor) => get_class($actor).'-'.$actor->getKey())
            ->values();
    }
}
