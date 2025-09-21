<?php

namespace App\Observers;

use App\Helpers\AuditHelper;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditObserver
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        $this->logModelEvent('created', $model);
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        // Only log if there are actual changes
        if ($model->wasChanged()) {
            $this->logModelEvent('updated', $model);
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->logModelEvent('deleted', $model);
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->logModelEvent('restored', $model);
    }

    /**
     * Handle the model "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        $this->logModelEvent('force_deleted', $model);
    }

    /**
     * Log model event with error handling.
     */
    protected function logModelEvent(string $eventType, Model $model): void
    {
        try {
            // Skip auditing for audit logs themselves to prevent infinite loops
            if ($model instanceof \App\Models\AuditLog) {
                return;
            }

            // Check if model should be audited
            if (! $this->shouldAuditModel($model)) {
                return;
            }

            $oldValues = [];
            $newValues = [];

            if ($eventType === 'updated') {
                $changes = $model->getChanges();
                $original = $model->getOriginal();

                foreach (array_keys($changes) as $key) {
                    // Skip if attribute shouldn't be audited
                    if (! $this->shouldAuditAttribute($model, $key)) {
                        continue;
                    }

                    $oldValues[$key] = $original[$key] ?? null;
                    $newValues[$key] = $changes[$key];
                }

                // Don't log if no auditable changes
                if (empty($oldValues) && empty($newValues)) {
                    return;
                }
            }

            $this->auditService->logModelEvent($eventType, $model, $oldValues, $newValues);

            // Log special events for specific models
            $this->logSpecialEvents($eventType, $model);

        } catch (\Exception $e) {
            Log::error('Audit observer failed to log model event', [
                'event_type' => $eventType,
                'model_class' => get_class($model),
                'model_id' => $model->getKey(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Check if a model should be audited.
     */
    protected function shouldAuditModel(Model $model): bool
    {
        // Skip certain models
        $skipModels = [
            \App\Models\AuditLog::class,
            \Laravel\Sanctum\PersonalAccessToken::class,
            \Illuminate\Notifications\DatabaseNotification::class,
        ];

        if (in_array(get_class($model), $skipModels)) {
            return false;
        }

        // Check if model has auditing disabled
        if (method_exists($model, 'shouldAudit') && ! $model->shouldAudit()) {
            return false;
        }

        return true;
    }

    /**
     * Check if an attribute should be audited.
     */
    protected function shouldAuditAttribute(Model $model, string $attribute): bool
    {
        // Use model's method if available
        if (method_exists($model, 'shouldAuditAttribute')) {
            return $model->shouldAuditAttribute($attribute);
        }

        // Default exclusions
        $excludedAttributes = [
            'created_at',
            'updated_at',
            'deleted_at',
            'password',
            'remember_token',
            'api_token',
            'email_verified_at',
        ];

        return ! in_array($attribute, $excludedAttributes);
    }

    /**
     * Log special events for specific models.
     */
    protected function logSpecialEvents(string $eventType, Model $model): void
    {
        $modelClass = get_class($model);

        // User-specific events
        if ($model instanceof \App\Models\User) {
            $this->logUserEvents($eventType, $model);
        }

        // Patient-specific events
        if (class_basename($modelClass) === 'Patients') {
            $this->logPatientEvents($eventType, $model);
        }

        // Score-specific events
        if ($model instanceof \App\Models\Score) {
            $this->logScoreEvents($eventType, $model);
        }

        // Post-specific events
        if (class_basename($modelClass) === 'Posts' || $model instanceof \App\Models\FeedPost) {
            $this->logPostEvents($eventType, $model);
        }
    }

    /**
     * Log user-specific events.
     */
    protected function logUserEvents(string $eventType, \App\Models\User $user): void
    {
        if ($eventType === 'created') {
            AuditHelper::log('user_registered', "New user registered: {$user->name} ({$user->email})", [
                'user_id' => $user->id,
                'registration_method' => 'standard',
            ], $user);
        }

        if ($eventType === 'updated') {
            $changes = $user->getChanges();

            // Log email changes
            if (isset($changes['email'])) {
                AuditHelper::logSecurityEvent('email_changed',
                    "User {$user->name} changed email from {$user->getOriginal('email')} to {$user->email}",
                    ['user_id' => $user->id]
                );
            }

            // Log password changes
            if (isset($changes['password'])) {
                AuditHelper::logSecurityEvent('password_changed',
                    "User {$user->name} changed their password",
                    ['user_id' => $user->id]
                );
            }

            // Log blocking/unblocking
            if (isset($changes['blocked'])) {
                $action = $changes['blocked'] ? 'blocked' : 'unblocked';
                AuditHelper::logSecurityEvent('user_'.$action,
                    "User {$user->name} was {$action}",
                    ['user_id' => $user->id]
                );
            }
        }
    }

    /**
     * Log patient-specific events.
     */
    protected function logPatientEvents(string $eventType, Model $patient): void
    {
        if ($eventType === 'created') {
            AuditHelper::log('patient_created', 'New patient created', [
                'patient_id' => $patient->getKey(),
                'doctor_id' => $patient->doctor_id ?? null,
            ], $patient);
        }

        if ($eventType === 'deleted') {
            AuditHelper::log('patient_deleted', 'Patient deleted', [
                'patient_id' => $patient->getKey(),
                'doctor_id' => $patient->doctor_id ?? null,
            ], $patient);
        }
    }

    /**
     * Log score-specific events.
     */
    protected function logScoreEvents(string $eventType, \App\Models\Score $score): void
    {
        if ($eventType === 'created' || $eventType === 'updated') {
            AuditHelper::log('score_changed', 'Score updated for user', [
                'score_id' => $score->id,
                'doctor_id' => $score->doctor_id,
                'new_score' => $score->score,
                'threshold' => $score->threshold,
            ], $score);
        }
    }

    /**
     * Log post-specific events.
     */
    protected function logPostEvents(string $eventType, Model $post): void
    {
        if ($eventType === 'created') {
            AuditHelper::log('post_created', 'New post created', [
                'post_id' => $post->getKey(),
                'doctor_id' => $post->doctor_id ?? null,
                'post_type' => class_basename(get_class($post)),
            ], $post);
        }

        if ($eventType === 'deleted') {
            AuditHelper::log('post_deleted', 'Post deleted', [
                'post_id' => $post->getKey(),
                'doctor_id' => $post->doctor_id ?? null,
                'post_type' => class_basename(get_class($post)),
            ], $post);
        }
    }
}
