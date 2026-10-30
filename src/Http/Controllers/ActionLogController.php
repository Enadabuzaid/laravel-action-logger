<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Http\Controllers;

use Enadstack\ActionLogger\Models\ActionLog;
use Enadstack\ActionLogger\Support\TenantLogQuery;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ActionLogController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->authorizeAccess();
    }

    /**
     * Display the action logger dashboard.
     */
    public function dashboard(): Response
    {
        $modules = $this->getModules();

        // Calculate stats
        $stats = [
            'total_logs' => $this->logs()->count(),
            'logs_last_24h' => $this->logs()->where('created_at', '>=', now()->subDay())->count(),
            'logs_last_7_days' => $this->logs()->where('created_at', '>=', now()->subDays(7))->count(),
            'logs_last_30_days' => $this->logs()->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        // Event counts
        $eventCounts = $this->logs()->selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();

        $stats['counts_per_event'] = $eventCounts;

        // Top modules (by subject_type)
        $topModules = $this->logs()->selectRaw('subject_type, COUNT(*) as count')
            ->groupBy('subject_type')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) use ($modules) {
                $moduleKey = $this->findModuleKeyBySubjectType($item->subject_type, $modules);

                return [
                    'subject_type' => $item->subject_type,
                    'module_key' => $moduleKey,
                    'label' => $moduleKey ? $modules[$moduleKey]['label'] : class_basename($item->subject_type),
                    'count' => $item->count,
                ];
            });

        $stats['top_modules'] = $topModules;

        // Top actors
        $topActors = $this->logs()->whereNotNull('actor_type')
            ->whereNotNull('actor_id')
            ->selectRaw('actor_type, actor_id, COUNT(*) as count')
            ->groupBy('actor_type', 'actor_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'actor_type' => class_basename($item->actor_type),
                    'actor_id' => $item->actor_id,
                    'count' => $item->count,
                ];
            });

        $stats['top_actors'] = $topActors;

        // Recent logs
        $recentLogs = $this->logs()->with(['subject', 'actor'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($log) => $this->formatLog($log));

        return Inertia::render('ActionLogger/Dashboard', [
            'stats' => $stats,
            'modules' => $modules,
            'recentLogs' => $recentLogs,
        ]);
    }

    /**
     * Display all logs with filters.
     */
    public function all(Request $request): Response
    {
        $filters = $request->only(['event', 'module_key', 'subject_type', 'actor_id', 'from', 'to', 'search']);

        $query = $this->logs()->with(['subject', 'actor'])->latest();

        // Apply filters
        if (!empty($filters['event'])) {
            $query->forEvent($filters['event']);
        }

        if (!empty($filters['module_key'])) {
            $modules = $this->getModules();
            if (isset($modules[$filters['module_key']])) {
                $query->where('subject_type', $modules[$filters['module_key']]['subject_type']);
            }
        }

        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (!empty($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id']);
        }

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        $logs = $query->paginate(config('action-logger.ui.per_page', 20))
            ->through(fn ($log) => $this->formatLog($log));

        return Inertia::render('ActionLogger/AllLogs', [
            'filters' => $filters,
            'logs' => $logs,
            'modules' => $this->getModules(),
            'events' => $this->getAvailableEvents(),
        ]);
    }

    /**
     * Display logs for a specific module.
     */
    public function module(string $moduleKey): Response
    {
        $modules = $this->getModules();

        abort_if(!isset($modules[$moduleKey]), 404, 'Module not found');

        $module = $modules[$moduleKey];
        $module['key'] = $moduleKey;

        $filters = request()->only(['event', 'from', 'to']);

        $query = $this->logs()->with(['subject', 'actor'])
            ->where('subject_type', $module['subject_type'])
            ->latest();

        // Apply filters
        if (!empty($filters['event'])) {
            $query->forEvent($filters['event']);
        }

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        $logs = $query->paginate(config('action-logger.ui.per_page', 20))
            ->through(fn ($log) => $this->formatLog($log));

        // Module stats
        $moduleStats = [
            'total' => $this->logs()->where('subject_type', $module['subject_type'])->count(),
            'last_24h' => $this->logs()->where('subject_type', $module['subject_type'])
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'last_7_days' => $this->logs()->where('subject_type', $module['subject_type'])
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];

        return Inertia::render('ActionLogger/ModuleLogs', [
            'module' => $module,
            'moduleStats' => $moduleStats,
            'logs' => $logs,
            'filters' => $filters,
            'events' => $this->getAvailableEvents(),
        ]);
    }

    /**
     * Display logs for a specific subject (record).
     */
    public function subject(string $subjectType, string|int $subjectId): Response
    {
        // Decode subject type (could be base64 encoded or class basename)
        $subjectClass = $this->resolveSubjectType($subjectType);

        abort_if(!$subjectClass || !class_exists($subjectClass), 404, 'Subject type not found');

        // Try to load the subject instance
        abort_if(
            !$this->logs()->where('subject_type', $subjectClass)->where('subject_id', $subjectId)->exists(),
            404,
            'Subject not found',
        );

        $subject = null;
        try {
            $subject = $subjectClass::find($subjectId);
        } catch (\Exception $e) {
            // Subject might not exist anymore
        }

        // Get logs for this subject
        $logs = $this->logs()->with('actor')
            ->where('subject_type', $subjectClass)
            ->where('subject_id', $subjectId)
            ->latest()
            ->paginate(config('action-logger.ui.per_page', 20))
            ->through(fn ($log) => $this->formatLog($log));

        // Prepare subject info
        $subjectInfo = [
            'id' => $subjectId,
            'class' => $subjectClass,
            'class_basename' => class_basename($subjectClass),
            'display' => $this->getSubjectDisplay($subject),
            'exists' => $subject !== null,
        ];

        return Inertia::render('ActionLogger/SubjectLogs', [
            'subject' => $subjectInfo,
            'logs' => $logs,
        ]);
    }

    /**
     * Tenant-scoped base query; every log read in this controller goes through it.
     *
     * @return \Illuminate\Database\Eloquent\Builder<ActionLog>
     */
    protected function logs(): \Illuminate\Database\Eloquent\Builder
    {
        return app(TenantLogQuery::class)->for(auth()->user());
    }

    /**
     * Get modules from configuration.
     */
    protected function getModules(): array
    {
        return config('action-logger.ui.modules', []);
    }

    /**
     * Get available events.
     */
    protected function getAvailableEvents(): array
    {
        return $this->logs()->distinct('event')
            ->pluck('event')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Format a log for API response.
     */
    protected function formatLog(ActionLog $log): array
    {
        return [
            'id' => $log->id,
            'event' => $log->event,
            'event_label' => $log->getEventLabel(),
            'event_color' => $log->getEventColor(),
            'event_icon' => $log->getEventIcon(),
            'description' => $log->getDescription(),
            'subject' => [
                'type' => class_basename($log->subject_type),
                'type_full' => $log->subject_type,
                'id' => $log->subject_id,
                'display' => $this->getSubjectDisplay($log->subject),
            ],
            'actor' => $log->actor ? [
                'type' => class_basename($log->actor_type),
                'id' => $log->actor_id,
                'name' => $this->getActorName($log->actor),
            ] : null,
            'changes' => $log->getChanges(),
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'meta' => $log->meta,
            'ip_address' => $log->ip_address,
            'created_at' => $log->created_at->format(config('action-logger.ui.date_format', 'M d, Y H:i')),
            'created_at_human' => $log->getTimeAgo(),
            'created_at_iso' => $log->created_at->toIso8601String(),
        ];
    }

    /**
     * Get display name for subject.
     */
    protected function getSubjectDisplay($subject): string
    {
        if (!$subject) {
            return 'Deleted';
        }

        // Try common name attributes
        foreach (['name', 'title', 'label', 'email', 'username'] as $attr) {
            if (isset($subject->$attr)) {
                return $subject->$attr;
            }
        }

        return class_basename($subject).' #'.$subject->getKey();
    }

    /**
     * Get display name for actor.
     */
    protected function getActorName($actor): string
    {
        if (!$actor) {
            return 'Unknown';
        }

        return $actor->name ?? $actor->email ?? $actor->username ?? 'User #'.$actor->getKey();
    }

    /**
     * Find module key by subject type.
     */
    protected function findModuleKeyBySubjectType(string $subjectType, array $modules): ?string
    {
        foreach ($modules as $key => $module) {
            if ($module['subject_type'] === $subjectType) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Resolve subject type from key.
     */
    protected function resolveSubjectType(string $subjectType): ?string
    {
        // Check if it's a module key
        $modules = $this->getModules();
        if (isset($modules[$subjectType])) {
            return $modules[$subjectType]['subject_type'];
        }

        // Check if it's base64 encoded
        $decoded = base64_decode($subjectType, true);
        if ($decoded !== false && base64_encode($decoded) === $subjectType) {
            if (class_exists($decoded)) {
                return $decoded;
            }
        }

        // Check if it's a valid class name from allowed list
        $allowedTypes = collect($modules)->pluck('subject_type')->toArray();
        $allowedTypes = array_merge($allowedTypes, $this->logs()->distinct('subject_type')->pluck('subject_type')->toArray());

        if (in_array($subjectType, $allowedTypes)) {
            return $subjectType;
        }

        return null;
    }

    /**
     * Authorize access to action logger UI.
     */
    protected function authorizeAccess(): void
    {
        $authorize = config('action-logger.ui.authorize');

        if ($authorize === null) {
            return; // No authorization required
        }

        if (is_string($authorize)) {
            // Gate or policy name
            Gate::authorize($authorize);
        } elseif (is_callable($authorize)) {
            // Custom callback
            $result = $authorize(auth()->user());
            abort_if(!$result, 403, 'Unauthorized to view action logs');
        }
    }
}
