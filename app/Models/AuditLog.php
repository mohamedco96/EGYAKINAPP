<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'audit_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'event_type',
        'auditable_type',
        'auditable_id',
        'user_id',
        'user_type',
        'user_name',
        'user_email',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'request_data',
        'old_values',
        'new_values',
        'changed_attributes',
        'tags',
        'description',
        'metadata',
        'session_id',
        'device_type',
        'platform',
        'location',
        'performed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'request_data' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_attributes' => 'array',
        'metadata' => 'array',
        'performed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        // Hide sensitive data by default
    ];

    /**
     * Get the auditable model that this log belongs to.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by auditable model.
     */
    public function scopeForModel($query, string $modelType, ?int $modelId = null)
    {
        $query = $query->where('auditable_type', $modelType);

        if ($modelId) {
            $query->where('auditable_id', $modelId);
        }

        return $query;
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate = null)
    {
        $query->where('performed_at', '>=', $startDate);

        if ($endDate) {
            $query->where('performed_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Scope to filter by IP address.
     */
    public function scopeByIp($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Get formatted description of the audit event.
     */
    public function getFormattedDescriptionAttribute(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $userName = $this->user_name ?? 'System';
        $modelName = class_basename($this->auditable_type ?? 'Unknown');

        return match ($this->event_type) {
            'created' => "{$userName} created a new {$modelName}",
            'updated' => "{$userName} updated {$modelName} #{$this->auditable_id}",
            'deleted' => "{$userName} deleted {$modelName} #{$this->auditable_id}",
            'login' => "{$userName} logged in",
            'logout' => "{$userName} logged out",
            'failed_login' => "Failed login attempt for {$this->user_email}",
            'password_reset' => "{$userName} reset their password",
            'email_verified' => "{$userName} verified their email",
            default => "{$userName} performed {$this->event_type} action"
        };
    }

    /**
     * Get the changes summary.
     */
    public function getChangesSummaryAttribute(): array
    {
        if (! $this->changed_attributes || ! is_array($this->changed_attributes)) {
            return [];
        }

        $summary = [];
        foreach ($this->changed_attributes as $attribute) {
            $oldValue = $this->old_values[$attribute] ?? null;
            $newValue = $this->new_values[$attribute] ?? null;

            $summary[$attribute] = [
                'from' => $oldValue,
                'to' => $newValue,
            ];
        }

        return $summary;
    }

    /**
     * Check if this audit log contains sensitive data.
     */
    public function hasSensitiveData(): bool
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'api_key'];

        if ($this->changed_attributes) {
            foreach ($sensitiveFields as $field) {
                if (in_array($field, $this->changed_attributes)) {
                    return true;
                }
            }
        }

        return false;
    }
}
