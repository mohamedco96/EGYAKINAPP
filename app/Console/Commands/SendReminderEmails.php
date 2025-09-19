<?php

namespace App\Console\Commands;

use App\Modules\Patients\Models\Patients;
use App\Modules\Patients\Models\PatientStatus;
use App\Notifications\ReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendReminderEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:send {--dry-run : Run without sending emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails to doctors for patients with submitted status but missing outcome after 72 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $remindersSent = 0;
        $remindersSkipped = 0;

        try {
            $this->info('🚀 Starting reminder email check...');
            if ($dryRun) {
                $this->warn('🔍 DRY RUN MODE: No emails will be sent');
            }

            // Find patient statuses that need reminders
            $patientsNeedingReminders = $this->getPatientsNeedingReminders();

            $this->info("📊 Found {$patientsNeedingReminders->count()} patient(s) needing outcome reminders");

            foreach ($patientsNeedingReminders as $submitStatus) {
                try {
                    $result = $this->processReminderForPatient($submitStatus, $dryRun);

                    if ($result['sent']) {
                        $remindersSent++;
                        $this->info("✅ Reminder processed for Patient ID: {$submitStatus->patient_id}, Doctor ID: {$submitStatus->doctor_id}");
                    } else {
                        $remindersSkipped++;
                        $this->warn("⚠️  Reminder skipped: {$result['reason']}");
                    }
                } catch (\Exception $e) {
                    $remindersSkipped++;
                    $this->error("❌ Failed to process reminder for Patient ID: {$submitStatus->patient_id} - {$e->getMessage()}");
                    Log::error('Failed to send individual reminder', [
                        'patient_id' => $submitStatus->patient_id,
                        'doctor_id' => $submitStatus->doctor_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // Summary
            $this->info('');
            $this->info('📋 Summary:');
            $this->info("✅ Reminders sent: {$remindersSent}");
            $this->info("⚠️  Reminders skipped: {$remindersSkipped}");
            $this->info('📧 Total processed: '.($remindersSent + $remindersSkipped));

            if ($dryRun) {
                $this->warn('🔍 This was a DRY RUN - no actual emails were sent');
            }

            Log::info('Reminder email job completed', [
                'reminders_sent' => $remindersSent,
                'reminders_skipped' => $remindersSkipped,
                'dry_run' => $dryRun,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            $this->error("❌ Error in reminder email job: {$e->getMessage()}");
            Log::error('Error in reminder email job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString(),
            ]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Get patients that need outcome reminders (submitted 72+ hours ago without outcome)
     */
    private function getPatientsNeedingReminders()
    {
        $cutoffTime = Carbon::now()->subHours(72);

        // Get all submit_status records that are older than 72 hours
        $submitStatuses = PatientStatus::where('key', 'submit_status')
            ->where('status', true)
            ->where('created_at', '<=', $cutoffTime)
            ->with(['patient', 'doctor'])
            ->get();

        // Filter out those that have outcome_status
        $patientsNeedingReminders = $submitStatuses->filter(function ($submitStatus) {
            // Check if there's an outcome_status for this patient and doctor
            $hasOutcome = PatientStatus::where('patient_id', $submitStatus->patient_id)
                ->where('doctor_id', $submitStatus->doctor_id)
                ->where('key', 'outcome_status')
                ->where('status', true)
                ->exists();

            return ! $hasOutcome;
        });

        return $patientsNeedingReminders;
    }

    /**
     * Process reminder for a specific patient
     */
    private function processReminderForPatient(PatientStatus $submitStatus, bool $dryRun = false): array
    {
        // Validate doctor exists
        if (! $submitStatus->doctor) {
            return [
                'sent' => false,
                'reason' => "Doctor not found for ID: {$submitStatus->doctor_id}",
            ];
        }

        // Validate patient exists
        if (! $submitStatus->patient) {
            return [
                'sent' => false,
                'reason' => "Patient not found for ID: {$submitStatus->patient_id}",
            ];
        }

        // Check if reminder was already sent recently (prevent spam)
        $recentReminder = PatientStatus::where('patient_id', $submitStatus->patient_id)
            ->where('doctor_id', $submitStatus->doctor_id)
            ->where('key', 'outcome_reminder_sent')
            ->where('status', true)
            ->where('created_at', '>=', Carbon::now()->subDays(7)) // Don't send more than once per week
            ->first();

        if ($recentReminder) {
            return [
                'sent' => false,
                'reason' => "Reminder already sent recently for Patient ID: {$submitStatus->patient_id}",
            ];
        }

        if ($dryRun) {
            return [
                'sent' => true,
                'reason' => 'DRY RUN - would send reminder email',
            ];
        }

        // Send the reminder email
        $submitStatus->doctor->notify(new ReminderNotification($submitStatus->patient, $submitStatus));

        // Mark that reminder was sent
        PatientStatus::create([
            'doctor_id' => $submitStatus->doctor_id,
            'patient_id' => $submitStatus->patient_id,
            'key' => 'outcome_reminder_sent',
            'status' => true,
        ]);

        Log::info('Reminder email sent successfully', [
            'doctor_id' => $submitStatus->doctor_id,
            'patient_id' => $submitStatus->patient_id,
            'submit_status_created_at' => $submitStatus->created_at,
            'hours_since_submit' => Carbon::now()->diffInHours($submitStatus->created_at),
            'timestamp' => now()->toISOString(),
        ]);

        return [
            'sent' => true,
            'reason' => 'Reminder email sent successfully',
        ];
    }
}
