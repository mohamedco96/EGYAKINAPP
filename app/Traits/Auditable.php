<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Auditable
{
    /**
     * Boot the auditable trait.
     */
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->auditModelEvent('created');
        });

        static::updated(function ($model) {
            $model->auditModelEvent('updated');
        });

        static::deleted(function ($model) {
            $model->auditModelEvent('deleted');
        });
    }

    /**
     * Get all audit logs for this model.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Get recent audit logs for this model.
     */
    public function recentAuditLogs(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->auditLogs()
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Audit a model event.
     */
    protected function auditModelEvent(string $eventType): void
    {
        $auditService = app(AuditService::class);

        $oldValues = [];
        $newValues = [];

        if ($eventType === 'updated') {
            $changes = $this->getChanges();
            $original = $this->getOriginal();

            foreach (array_keys($changes) as $key) {
                $oldValues[$key] = $original[$key] ?? null;
                $newValues[$key] = $changes[$key];
            }
        }

        $auditService->logModelEvent($eventType, $this, $oldValues, $newValues);
    }

    /**
     * Manually log a custom audit event for this model.
     */
    public function auditCustomEvent(string $eventType, ?string $description = null, array $metadata = []): ?AuditLog
    {
        $auditService = app(AuditService::class);

        return $auditService->logCustomEvent($eventType, $description, $metadata, $this);
    }

    /**
     * Get audit logs by event type.
     */
    public function auditLogsByEvent(string $eventType): \Illuminate\Database\Eloquent\Collection
    {
        return $this->auditLogs()
            ->where('event_type', $eventType)
            ->orderBy('performed_at', 'desc')
            ->get();
    }

    /**
     * Get audit logs by user.
     */
    public function auditLogsByUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->auditLogs()
            ->where('user_id', $userId)
            ->orderBy('performed_at', 'desc')
            ->get();
    }

    /**
     * Get audit logs within a date range.
     */
    public function auditLogsInDateRange($startDate, $endDate = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->auditLogs()
            ->where('performed_at', '>=', $startDate);

        if ($endDate) {
            $query->where('performed_at', '<=', $endDate);
        }

        return $query->orderBy('performed_at', 'desc')->get();
    }

    /**
     * Check if this model has any audit logs.
     */
    public function hasAuditLogs(): bool
    {
        return $this->auditLogs()->exists();
    }

    /**
     * Get the first audit log (creation log).
     */
    public function firstAuditLog(): ?AuditLog
    {
        return $this->auditLogs()
            ->orderBy('performed_at', 'asc')
            ->first();
    }

    /**
     * Get the last audit log.
     */
    public function lastAuditLog(): ?AuditLog
    {
        return $this->auditLogs()
            ->orderBy('performed_at', 'desc')
            ->first();
    }

    /**
     * Get audit summary for this model.
     */
    public function getAuditSummary(): array
    {
        $logs = $this->auditLogs;

        $summary = [
            'total_events' => $logs->count(),
            'first_event' => $logs->min('performed_at'),
            'last_event' => $logs->max('performed_at'),
            'event_types' => $logs->groupBy('event_type')->map->count()->toArray(),
            'users_involved' => $logs->whereNotNull('user_id')->unique('user_id')->count(),
        ];

        return $summary;
    }

    /**
     * Determine which attributes should be audited.
     * Override this method in your model to customize audited attributes.
     */
    public function getAuditableAttributes(): array
    {
        // By default, audit all fillable attributes
        return $this->getFillable();
    }

    /**
     * Determine which attributes should be excluded from auditing.
     * Override this method in your model to exclude sensitive attributes.
     */
    public function getAuditExcludedAttributes(): array
    {
        return [
            'password',
            'remember_token',
            'api_token',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Check if an attribute should be audited.
     */
    public function shouldAuditAttribute(string $attribute): bool
    {
        $auditableAttributes = $this->getAuditableAttributes();
        $excludedAttributes = $this->getAuditExcludedAttributes();

        // If auditable attributes are specified, only audit those
        if (! empty($auditableAttributes)) {
            return in_array($attribute, $auditableAttributes) && ! in_array($attribute, $excludedAttributes);
        }

        // Otherwise, audit all except excluded
        return ! in_array($attribute, $excludedAttributes);
    }
}
