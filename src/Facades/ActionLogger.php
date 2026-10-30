<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Facades;

use Enadstack\ActionLogger\Models\ActionLog;
use Enadstack\ActionLogger\Services\ActionLogger as ActionLoggerService;
use Illuminate\Support\Facades\Facade;

/**
 * ActionLogger Facade
 *
 * @method static ActionLog|null log(object $subject, ?object $actor, string $event, ?array $oldValues = null, ?array $newValues = null, ?array $meta = null)
 * @method static ActionLoggerService for(object $subject)
 * @method static ActionLoggerService by(?object $actor)
 * @method static ActionLoggerService event(string $event)
 * @method static ActionLoggerService withOld(array $old)
 * @method static ActionLoggerService withNew(array $new)
 * @method static ActionLoggerService withMeta(array $meta)
 * @method static ActionLoggerService addMeta(string $key, mixed $value)
 * @method static ActionLog|null save()
 * @method static ActionLoggerService resolveTenantUsing(callable $callback)
 * @method static int|string|null resolveTenantId(?object $subject, ?object $actor)
 * @method static ActionLoggerService reset()
 * @method static ActionLoggerService make()
 *
 * @see \Enadstack\ActionLogger\Services\ActionLogger
 */
class ActionLogger extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ActionLoggerService::class;
    }
}
