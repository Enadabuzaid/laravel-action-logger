<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * ActionLog Model
 *
 * @property int $id
 * @property string $subject_type
 * @property int $subject_id
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string $event
 * @property array|null $old_values
 * @property array|null $new_values
 * @property array|null $meta
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int|null $tenant_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Model|null $subject
 * @property-read Model|null $actor
 *
 * @method static Builder forSubject(Model $subject)
 * @method static Builder forActor(Model $actor)
 * @method static Builder forEvent(string $event)
 * @method static Builder forEvents(array $events)
 * @method static Builder betweenDates(Carbon|string $from, Carbon|string $to)
 * @method static Builder forTenant(int|string|null $tenantId)
 * @method static Builder recent(int $days = 7)
 * @method static Builder today()
 * @method static Builder systemActions()
 * @method static Builder userActions()
 * @method static Builder withAttributeChange(string $attribute)
 * @method static Builder fromIp(string $ipAddress)
 * @method static Builder withMeta()
 * @method static Builder withMetaKey(string $key)
 */
class ActionLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'action_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subject_type',
        'subject_id',
        'actor_type',
        'actor_id',
        'event',
        'old_values',
        'new_values',
        'meta',
        'ip_address',
        'user_agent',
        'tenant_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the subject (audited model) that this log belongs to.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject');
    }

    /**
     * Get the actor (user/system) that performed this action.
     */
    public function actor(): MorphTo
    {
        return $this->morphTo('actor');
    }

    /**
     * Scope a query to only include logs for a specific subject.
     */
    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    /**
     * Scope a query to only include logs for a specific actor.
     */
    public function scopeForActor(Builder $query, Model $actor): Builder
    {
        return $query->where('actor_type', $actor->getMorphClass())
            ->where('actor_id', $actor->getKey());
    }

    /**
     * Scope a query to only include logs for a specific event.
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Scope a query to only include logs between specific dates.
     */
    public function scopeBetweenDates(Builder $query, Carbon|string $from, Carbon|string $to): Builder
    {
        $from = $from instanceof Carbon ? $from : Carbon::parse($from);
        $to = $to instanceof Carbon ? $to : Carbon::parse($to);

        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope a query to only include logs for a specific tenant.
     */
    public function scopeForTenant(Builder $query, int|string|null $tenantId): Builder
    {
        if ($tenantId === null) {
            return $query->whereNull(static::getTenantColumn());
        }

        return $query->where(static::getTenantColumn(), $tenantId);
    }

    /**
     * Get the tenant column name from configuration.
     */
    public static function getTenantColumn(): string
    {
        return config('action-logger.tenant.column', 'tenant_id');
    }

    /**
     * Get a human-readable description of the action.
     */
    public function getDescription(): string
    {
        $subjectName = class_basename($this->subject_type);
        $actorName = $this->actor_type ? class_basename($this->actor_type) : 'System';

        return sprintf(
            '%s %s %s #%s',
            $actorName,
            $this->event,
            $subjectName,
            $this->subject_id,
        );
    }

    /**
     * Get the changes made (diff between old and new values).
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function getChanges(): array
    {
        if (empty($this->old_values) || empty($this->new_values)) {
            return [];
        }

        $changes = [];
        $allKeys = array_unique(array_merge(
            array_keys($this->old_values),
            array_keys($this->new_values),
        ));

        foreach ($allKeys as $key) {
            $oldValue = $this->old_values[$key] ?? null;
            $newValue = $this->new_values[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Check if this log represents a creation event.
     */
    public function isCreated(): bool
    {
        return $this->event === 'created';
    }

    /**
     * Check if this log represents an update event.
     */
    public function isUpdated(): bool
    {
        return $this->event === 'updated';
    }

    /**
     * Check if this log represents a deletion event.
     */
    public function isDeleted(): bool
    {
        return $this->event === 'deleted';
    }

    /**
     * Check if this log represents a restoration event.
     */
    public function isRestored(): bool
    {
        return $this->event === 'restored';
    }

    /**
     * Scope a query to only include recent logs (last N days).
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to only include logs from today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope a query to only include system actions (no actor).
     */
    public function scopeSystemActions(Builder $query): Builder
    {
        return $query->whereNull('actor_type')->whereNull('actor_id');
    }

    /**
     * Scope a query to only include user actions (has actor).
     */
    public function scopeUserActions(Builder $query): Builder
    {
        return $query->whereNotNull('actor_type')->whereNotNull('actor_id');
    }

    /**
     * Scope a query to only include logs for multiple events.
     */
    public function scopeForEvents(Builder $query, array $events): Builder
    {
        return $query->whereIn('event', $events);
    }

    /**
     * Scope a query to only include logs with changes to a specific attribute.
     */
    public function scopeWithAttributeChange(Builder $query, string $attribute): Builder
    {
        return $query->where(function ($q) use ($attribute) {
            $q->whereJsonContains('old_values->'.$attribute, true)
                ->orWhereJsonContains('new_values->'.$attribute, true);
        });
    }

    /**
     * Scope a query to only include logs from a specific IP address.
     */
    public function scopeFromIp(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope a query to only include logs with metadata.
     */
    public function scopeWithMeta(Builder $query): Builder
    {
        return $query->whereNotNull('meta');
    }

    /**
     * Scope a query to only include logs with a specific meta key.
     */
    public function scopeWithMetaKey(Builder $query, string $key): Builder
    {
        return $query->whereJsonContains('meta->'.$key, true);
    }

    /**
     * Check if this is a system action (no actor).
     */
    public function isSystemAction(): bool
    {
        return $this->actor_type === null && $this->actor_id === null;
    }

    /**
     * Check if this is a user action (has actor).
     */
    public function isUserAction(): bool
    {
        return $this->actor_type !== null && $this->actor_id !== null;
    }

    /**
     * Get a specific meta value.
     */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }

    /**
     * Check if meta has a specific key.
     */
    public function hasMeta(string $key): bool
    {
        return isset($this->meta[$key]);
    }

    /**
     * Get the change for a specific attribute.
     *
     * @return array{old: mixed, new: mixed}|null
     */
    public function getChangeFor(string $attribute): ?array
    {
        $changes = $this->getChanges();

        return $changes[$attribute] ?? null;
    }

    /**
     * Check if a specific attribute was changed.
     */
    public function hasChangeFor(string $attribute): bool
    {
        return isset($this->getChanges()[$attribute]);
    }

    /**
     * Get all changed attribute names.
     */
    public function getChangedAttributes(): array
    {
        return array_keys($this->getChanges());
    }

    /**
     * Get a formatted timestamp.
     */
    public function getFormattedDate(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->created_at->format($format);
    }

    /**
     * Get a human-readable time difference.
     */
    public function getTimeAgo(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the event name in a human-readable format.
     */
    public function getEventLabel(): string
    {
        return ucfirst(str_replace('_', ' ', $this->event));
    }

    /**
     * Get a badge color for the event type (useful for UI).
     */
    public function getEventColor(): string
    {
        return match ($this->event) {
            'created' => 'green',
            'updated' => 'blue',
            'deleted' => 'red',
            'restored' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Get an icon name for the event type (useful for UI).
     */
    public function getEventIcon(): string
    {
        return match ($this->event) {
            'created' => 'plus-circle',
            'updated' => 'edit',
            'deleted' => 'trash',
            'restored' => 'refresh',
            default => 'activity',
        };
    }

    /**
     * Convert to array for API responses.
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'event_label' => $this->getEventLabel(),
            'description' => $this->getDescription(),
            'subject' => [
                'type' => class_basename($this->subject_type),
                'id' => $this->subject_id,
            ],
            'actor' => $this->actor ? [
                'type' => class_basename($this->actor_type),
                'id' => $this->actor_id,
                'name' => $this->actor->name ?? null,
            ] : null,
            'changes' => $this->getChanges(),
            'meta' => $this->meta,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at->toIso8601String(),
            'time_ago' => $this->getTimeAgo(),
        ];
    }
}
