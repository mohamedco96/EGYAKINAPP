<?php

namespace App\Console\Commands;

use App\Models\Answers;
use App\Modules\Patients\Models\Patients;
use App\Modules\Patients\Models\PatientStatus;
use App\Notifications\ReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TestReminderEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:test 
                            {--hours=1 : Hours to look back instead of 72}
                            {--email= : Send test reminder to specific email address}
                            {--dry-run : Run without sending emails}
                            {--create-test-data : Create test patient status data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test reminder emails with custom time threshold and email for development';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');
        $testEmail = $this->option('email');
        $dryRun = $this->option('dry-run');
        $createTestData = $this->option('create-test-data');

        $this->info('🧪 Starting REMINDER EMAIL TESTING');
        $this->info("⏰ Looking back: {$hours} hours (instead of 72)");

        if ($testEmail) {
            $this->info("📧 Test email: {$testEmail}");
        }

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE: No emails will be sent');
        }

        // Create test data if requested
        if ($createTestData) {
            $this->createTestData($hours);
        }

        $remindersSent = 0;
        $remindersSkipped = 0;

        try {
            // Find patient statuses that need reminders (using custom hours)
            $patientsNeedingReminders = $this->getPatientsNeedingReminders($hours);

            $this->info("📊 Found {$patientsNeedingReminders->count()} patient(s) needing outcome reminders");

            if ($patientsNeedingReminders->isEmpty()) {
                $this->warn('⚠️  No patients found needing reminders. Try:');
                $this->warn('   1. Use --create-test-data to create sample data');
                $this->warn('   2. Reduce --hours to look at more recent records');
                $this->warn('   3. Check your database for submit_status records');

                return Command::SUCCESS;
            }

            foreach ($patientsNeedingReminders as $submitStatus) {
                try {
                    $result = $this->processReminderForPatient($submitStatus, $dryRun, $testEmail);

                    if ($result['sent']) {
                        $remindersSent++;
                        $this->info("✅ Reminder processed for Patient ID: {$submitStatus->patient_id}, Doctor ID: {$submitStatus->doctor_id}");
                        $this->info("   📧 Email: {$result['email']}");
                        $this->info("   ⏰ Hours since submit: {$result['hours_since_submit']}");
                    } else {
                        $remindersSkipped++;
                        $this->warn("⚠️  Reminder skipped: {$result['reason']}");
                    }
                } catch (\Exception $e) {
                    $remindersSkipped++;
                    $this->error("❌ Failed to process reminder for Patient ID: {$submitStatus->patient_id} - {$e->getMessage()}");
                }
            }

            // Summary
            $this->info('');
            $this->info('📋 TEST SUMMARY:');
            $this->info("✅ Reminders sent: {$remindersSent}");
            $this->info("⚠️  Reminders skipped: {$remindersSkipped}");
            $this->info('📧 Total processed: '.($remindersSent + $remindersSkipped));

            if ($dryRun) {
                $this->warn('🔍 This was a DRY RUN - no actual emails were sent');
            }

        } catch (\Exception $e) {
            $this->error("❌ Error in test reminder email job: {$e->getMessage()}");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Create test data for testing
     */
    private function createTestData(int $hoursBack): void
    {
        $this->info('🔧 Creating test patient status data...');

        try {
            // Find or create a test doctor (first user)
            $doctor = \App\Models\User::first();
            if (! $doctor) {
                $this->error('❌ No users found in database. Please create a user first.');

                return;
            }

            // Find or create a test patient
            $patient = Patients::first();
            if (! $patient) {
                $patient = Patients::create([
                    'doctor_id' => $doctor->id,
                    'hidden' => false,
                ]);
                $this->info("✅ Created test patient ID: {$patient->id}");
            }

            // Create submit_status record from X hours ago
            $submitTime = Carbon::now()->subHours($hoursBack + 1); // Make it older than threshold

            // Delete existing test data for this patient/doctor combo
            PatientStatus::where('patient_id', $patient->id)
                ->where('doctor_id', $doctor->id)
                ->delete();

            // Create submit_status
            $submitStatus = PatientStatus::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'key' => 'submit_status',
                'status' => true,
                'created_at' => $submitTime,
                'updated_at' => $submitTime,
            ]);

            // Create patient name answer (question_id = 1)
            $patientName = 'Test Patient '.$patient->id;
            Answers::updateOrCreate(
                [
                    'patient_id' => $patient->id,
                    'question_id' => 1,
                    'doctor_id' => $doctor->id,
                ],
                [
                    'section_id' => 1, // Assuming section 1 exists
                    'answer' => $patientName,
                    'type' => 'text',
                    'created_at' => $submitTime,
                    'updated_at' => $submitTime,
                ]
            );

            $this->info('✅ Created submit_status record:');
            $this->info("   👨‍⚕️ Doctor ID: {$doctor->id} ({$doctor->name})");
            $this->info("   🏥 Patient ID: {$patient->id}");
            $this->info("   👤 Patient Name: {$patientName} (from answers table, question_id=1)");
            $this->info("   ⏰ Created: {$submitTime->format('Y-m-d H:i:s')} ({$submitTime->diffForHumans()})");
            $this->info('   📝 Key: submit_status, Status: true');

        } catch (\Exception $e) {
            $this->error("❌ Failed to create test data: {$e->getMessage()}");
        }
    }

    /**
     * Get patients that need outcome reminders (using custom hours)
     */
    private function getPatientsNeedingReminders(int $hours)
    {
        $cutoffTime = Carbon::now()->subHours($hours);

        $this->info("🔍 Searching for submit_status records older than: {$cutoffTime->format('Y-m-d H:i:s')}");

        // Get all submit_status records that are older than X hours
        $submitStatuses = PatientStatus::where('key', 'submit_status')
            ->where('status', true)
            ->where('created_at', '<=', $cutoffTime)
            ->with(['patient', 'doctor'])
            ->get();

        $this->info("📋 Found {$submitStatuses->count()} submit_status records older than {$hours} hours");

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
     * Process reminder for a specific patient (test version)
     */
    private function processReminderForPatient(PatientStatus $submitStatus, bool $dryRun = false, ?string $testEmail = null): array
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

        // Calculate hours since submit
        $hoursSinceSubmit = Carbon::now()->diffInHours($submitStatus->created_at);

        // Use test email if provided, otherwise use doctor's email
        $emailAddress = $testEmail ?: $submitStatus->doctor->email;

        if ($dryRun) {
            return [
                'sent' => true,
                'reason' => 'DRY RUN - would send reminder email',
                'email' => $emailAddress,
                'hours_since_submit' => $hoursSinceSubmit,
            ];
        }

        // Create a temporary user object with test email if needed
        if ($testEmail) {
            $notifiableUser = new \App\Models\User();
            $notifiableUser->email = $testEmail;
            $notifiableUser->name = 'Test User';
            $notifiableUser->id = $submitStatus->doctor_id;
        } else {
            $notifiableUser = $submitStatus->doctor;
        }

        // Send the reminder email
        $notifiableUser->notify(new ReminderNotification($submitStatus->patient, $submitStatus));

        // For testing, don't create tracking record to allow repeated testing
        if (! $testEmail) {
            // Mark that reminder was sent (only in production mode)
            PatientStatus::create([
                'doctor_id' => $submitStatus->doctor_id,
                'patient_id' => $submitStatus->patient_id,
                'key' => 'outcome_reminder_sent',
                'status' => true,
            ]);
        }

        Log::info('TEST: Reminder email sent successfully', [
            'doctor_id' => $submitStatus->doctor_id,
            'patient_id' => $submitStatus->patient_id,
            'test_email' => $testEmail,
            'actual_email' => $emailAddress,
            'submit_status_created_at' => $submitStatus->created_at,
            'hours_since_submit' => $hoursSinceSubmit,
            'timestamp' => now()->toISOString(),
        ]);

        return [
            'sent' => true,
            'reason' => 'Test reminder email sent successfully',
            'email' => $emailAddress,
            'hours_since_submit' => $hoursSinceSubmit,
        ];
    }
}
