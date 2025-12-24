<?php

declare(strict_types=1);

use Enadstack\ActionLogger\Services\ActionLogger;

if (!function_exists('action_logger')) {
    /**
     * Get the ActionLogger service instance.
     */
    function action_logger(): ActionLogger
    {
        return app(ActionLogger::class);
    }
}

if (!function_exists('log_action')) {
    /**
     * Log an action quickly using a helper function.
     *
     * @param object $subject The model being audited
     * @param object|null $actor The user/system performing the action
     * @param string $event The event name
     * @param array|null $oldValues Previous state
     * @param array|null $newValues New state
     * @param array|null $meta Additional metadata
     */
    function log_action(
        object $subject,
        ?object $actor,
        string $event,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $meta = null,
    ): ?\Enadstack\ActionLogger\Models\ActionLog {
        return action_logger()->log($subject, $actor, $event, $oldValues, $newValues, $meta);
    }
}
