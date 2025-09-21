<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAuditLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $auditData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $auditData)
    {
        $this->auditData = $auditData;
        $this->onQueue('audit');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            AuditLog::create([
                'event_type' => $this->auditData['event_type'],
                'auditable_type' => $this->auditData['auditable_type'] ?? null,
                'auditable_id' => $this->auditData['auditable_id'] ?? null,
                'user_id' => $this->auditData['user_id'] ?? null,
                'user_type' => $this->auditData['user_type'] ?? null,
                'user_name' => $this->auditData['user_name'] ?? null,
                'user_email' => $this->auditData['user_email'] ?? null,
                'ip_address' => $this->auditData['ip_address'] ?? null,
                'user_agent' => $this->auditData['user_agent'] ?? null,
                'url' => $this->auditData['url'] ?? null,
                'method' => $this->auditData['method'] ?? null,
                'request_data' => $this->auditData['request_data'] ?? null,
                'old_values' => $this->auditData['old_values'] ?? null,
                'new_values' => $this->auditData['new_values'] ?? null,
                'changed_attributes' => $this->auditData['changed_attributes'] ?? null,
                'tags' => $this->auditData['tags'] ?? null,
                'description' => $this->auditData['description'] ?? null,
                'metadata' => $this->auditData['metadata'] ?? null,
                'session_id' => $this->auditData['session_id'] ?? null,
                'device_type' => $this->auditData['device_type'] ?? null,
                'platform' => $this->auditData['platform'] ?? null,
                'location' => $this->auditData['location'] ?? null,
                'performed_at' => $this->auditData['performed_at'] ?? now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process audit log', [
                'audit_data' => $this->auditData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Audit log job failed', [
            'audit_data' => $this->auditData,
            'error' => $exception->getMessage(),
        ]);
    }
}
