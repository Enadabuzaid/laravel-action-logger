<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Services;

use Enadstack\ActionLogger\Contracts\TenantResolverInterface;
use Enadstack\ActionLogger\Models\ActionLog;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

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

    protected ?string $module = null;

    protected string $severity = ActionLog::SEVERITY_INFO;

    protected ?string $requestId = null;

    /**
     * Create a new ActionLogger instance.
     */
    public function __construct(
        protected TenantResolverInterface $tenantResolver,
    ) {}

    /**
     * Log an action directly.
     *
     * @param object|null $subject The model being audited (null for subject-less system events)
     * @param object|null $actor The user/system performing the action
     * @param string $event The event name (created, updated, deleted, etc.)
     * @param array|null $oldValues Previous state of the model
     * @param array|null $newValues New state of the model
     * @param array|null $meta Additional metadata
     * @param string|null $module Module/area the event belongs to
     * @param string $severity One of info, warning, critical
     * @param string|null $requestId Explicit request id (defaults to the current request's id)
     * @return ActionLog|null The created log, or null if logging is disabled
     */
    public function log(
        ?object $subject,
        ?object $actor,
        string $event,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $meta = null,
        ?string $module = null,
        string $severity = ActionLog::SEVERITY_INFO,
        ?string $requestId = null,
    ): ?ActionLog {
        if (!in_array($severity, [ActionLog::SEVERITY_INFO, ActionLog::SEVERITY_WARNING, ActionLog::SEVERITY_CRITICAL], true)) {
            throw new \InvalidArgumentException("Invalid severity [{$severity}]. Expected info, warning or critical.");
        }

        // Check if logging is enabled
        if (!config('action-logger.enabled', true)) {
            return null;
        }

        // Resolve tenant ID
        $tenantId = $subject !== null ? $this->tenantResolver->resolveTenantId($subject, $actor) : null;

        // Get request context
        $ipAddress = $this->getIpAddress();
        $userAgent = $this->getUserAgent();

        // Create the log
        return ActionLog::create([
            'subject_type' => $subject !== null ? $this->getMorphClass($subject) : null,
            'subject_id' => $subject !== null ? $this->getModelKey($subject) : null,
            'actor_type' => $actor ? $this->getMorphClass($actor) : null,
            'actor_id' => $actor ? $this->getModelKey($actor) : null,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'meta' => $meta,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'tenant_id' => $tenantId,
            'module' => $module,
            'severity' => $severity,
            'request_id' => $this->resolveRequestId($requestId),
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
     * Set the module (fluent).
     *
     * @return $this
     */
    public function module(string $module): self
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Set the severity (fluent).
     *
     * @return $this
     */
    public function severity(string $severity): self
    {
        $this->severity = $severity;

        return $this;
    }

    /**
     * Set an explicit request id (fluent).
     *
     * @return $this
     */
    public function requestId(string $requestId): self
    {
        $this->requestId = $requestId;

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
            module: $this->module,
            severity: $this->severity,
            requestId: $this->requestId,
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
        $this->module = null;
        $this->severity = ActionLog::SEVERITY_INFO;
        $this->requestId = null;

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
     * Resolve the request id: explicit value, X-Request-ID header, or a uuid
     * generated once per request. Null when no request is bound.
     */
    protected function resolveRequestId(?string $explicit): ?string
    {
        if ($explicit !== null) {
            return $explicit;
        }

        if (!app()->bound('request')) {
            return null;
        }

        $request = app('request');
        $header = $request->headers->get('X-Request-ID');

        if (is_string($header) && $header !== '') {
            return $header;
        }

        $id = $request->attributes->get('action_logger.request_id');

        if (!is_string($id) || $id === '') {
            $id = Str::uuid()->toString();
            $request->attributes->set('action_logger.request_id', $id);
        }

        return $id;
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
