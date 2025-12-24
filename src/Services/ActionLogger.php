<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Services;

use Enadstack\ActionLogger\Contracts\TenantResolverInterface;
use Enadstack\ActionLogger\Models\ActionLog;
use Illuminate\Support\Facades\Request;

/**
 * ActionLogger Service
 *
 * Central service for creating action logs with support for both
 * direct method calls and fluent builder pattern.
 *
 * @example Direct usage:
 * $logger->log($post, $user, 'updated', ['title' => 'Old'], ['title' => 'New']);
 * @example Fluent usage:
 * $logger->for($post)
 *        ->by($user)
 *        ->event('updated')
 *        ->withOld(['title' => 'Old'])
 *        ->withNew(['title' => 'New'])
 *        ->save();
 */
class ActionLogger
{
    /**
     * Fluent builder state.
     */
    protected ?object $subject = null;

    protected ?object $actor = null;

    protected ?string $event = null;

    protected ?array $oldValues = null;

    protected ?array $newValues = null;

    protected ?array $meta = null;

    /**
     * Create a new ActionLogger instance.
     */
    public function __construct(
        protected TenantResolverInterface $tenantResolver,
    ) {}

    /**
     * Log an action directly.
     *
     * @param object $subject The model being audited
     * @param object|null $actor The user/system performing the action
     * @param string $event The event name (created, updated, deleted, etc.)
     * @param array|null $oldValues Previous state of the model
     * @param array|null $newValues New state of the model
     * @param array|null $meta Additional metadata
     * @return ActionLog|null The created log, or null if logging is disabled
     */
    public function log(
        object $subject,
        ?object $actor,
        string $event,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $meta = null,
    ): ?ActionLog {
        // Check if logging is enabled
        if (!config('action-logger.enabled', true)) {
            return null;
        }

        // Resolve tenant ID
        $tenantId = $this->tenantResolver->resolveTenantId($subject, $actor);

        // Get request context
        $ipAddress = $this->getIpAddress();
        $userAgent = $this->getUserAgent();

        // Create the log
        return ActionLog::create([
            'subject_type' => $this->getMorphClass($subject),
            'subject_id' => $this->getModelKey($subject),
            'actor_type' => $actor ? $this->getMorphClass($actor) : null,
            'actor_id' => $actor ? $this->getModelKey($actor) : null,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'meta' => $meta,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Set the subject (fluent).
     *
     * @param object $subject The model being audited
     * @return $this
     */
    public function for(object $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set the actor (fluent).
     *
     * @param object|null $actor The user/system performing the action
     * @return $this
     */
    public function by(?object $actor): self
    {
        $this->actor = $actor;

        return $this;
    }

    /**
     * Set the event (fluent).
     *
     * @param string $event The event name
     * @return $this
     */
    public function event(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Set old values (fluent).
     *
     * @param array $old Previous state
     * @return $this
     */
    public function withOld(array $old): self
    {
        $this->oldValues = $old;

        return $this;
    }

    /**
     * Set new values (fluent).
     *
     * @param array $new New state
     * @return $this
     */
    public function withNew(array $new): self
    {
        $this->newValues = $new;

        return $this;
    }

    /**
     * Set metadata (fluent).
     *
     * @param array $meta Additional metadata
     * @return $this
     */
    public function withMeta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Add a single meta key-value pair (fluent).
     *
     * @return $this
     */
    public function addMeta(string $key, mixed $value): self
    {
        if ($this->meta === null) {
            $this->meta = [];
        }

        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * Save the log using fluent builder state.
     *
     * @throws \InvalidArgumentException If required fields are missing
     */
    public function save(): ?ActionLog
    {
        if ($this->subject === null) {
            throw new \InvalidArgumentException('Subject is required. Use for($model) to set it.');
        }

        if ($this->event === null) {
            throw new \InvalidArgumentException('Event is required. Use event($name) to set it.');
        }

        $log = $this->log(
            subject: $this->subject,
            actor: $this->actor,
            event: $this->event,
            oldValues: $this->oldValues,
            newValues: $this->newValues,
            meta: $this->meta,
        );

        // Reset builder state after saving
        $this->reset();

        return $log;
    }

    /**
     * Reset the fluent builder state.
     *
     * @return $this
     */
    public function reset(): self
    {
        $this->subject = null;
        $this->actor = null;
        $this->event = null;
        $this->oldValues = null;
        $this->newValues = null;
        $this->meta = null;

        return $this;
    }

    /**
     * Get the morph class name for a model.
     */
    protected function getMorphClass(object $model): string
    {
        // If it's an Eloquent model, use getMorphClass()
        if (method_exists($model, 'getMorphClass')) {
            return $model->getMorphClass();
        }

        // Otherwise, use the class name
        return get_class($model);
    }

    /**
     * Get the model key (ID).
     */
    protected function getModelKey(object $model): int|string
    {
        // If it's an Eloquent model, use getKey()
        if (method_exists($model, 'getKey')) {
            return $model->getKey();
        }

        // Try common ID properties
        if (property_exists($model, 'id')) {
            return $model->id;
        }

        if (isset($model->id)) {
            return $model->id;
        }

        // Fallback to object hash
        return spl_object_id($model);
    }

    /**
     * Get the current request IP address.
     */
    protected function getIpAddress(): ?string
    {
        if (!app()->bound('request')) {
            return null;
        }

        return Request::ip();
    }

    /**
     * Get the current request user agent.
     */
    protected function getUserAgent(): ?string
    {
        if (!app()->bound('request')) {
            return null;
        }

        return Request::userAgent();
    }

    /**
     * Create a new instance for fluent usage (static factory).
     */
    public static function make(): static
    {
        return app(static::class);
    }
}
