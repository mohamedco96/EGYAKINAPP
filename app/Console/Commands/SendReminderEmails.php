<?php

namespace App\Console\Commands;

use App\Models\PatientHistory;
use App\Models\Section;
use App\Models\User;
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
    protected $signature = 'reminder:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails to users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Get events that happened 72 hours ago
            $events = Section::where('final_submited_at', '<=', Carbon::now()->subHours(72))->get();

            foreach ($events as $event) {
                // Log an informational message
                Log::info('Reminder email after 72 hours sent successfully for doctor_id ' . $event->doctor_id);
                // Send reminder email to users associated with the event
                $this->sendReminderEmail($event);
                // Update last reminder sent timestamp
                $event->update(['last_final_submit_reminder' => now(), 'final_submited_at' => null]);
            }

            // Get events that happened one week ago
            $lastReminderEvents = Section::where('last_final_submit_reminder', '<=', Carbon::now()->subWeek())->get();

            foreach ($lastReminderEvents as $event) {
                Log::info('Reminder email after one week sent successfully for doctor_id ' . $event->doctor_id);
                // Send reminder email to users associated with the event
                $this->sendReminderEmail($event);
                $event->update(['last_final_submit_reminder' => null]);
            }
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error sending reminder email: ' . $e->getMessage());
        }
    }

    /**
     * Send reminder email to the user associated with the event.
     *
     * @param \App\Models\Section $event
     */
    private function sendReminderEmail(Section $event)
    {
        // Get the user associated with the event
        $user = User::find($event->doctor_id);
        $patient = PatientHistory::find($event->patient_id);
        // Send reminder email to the user
        $user->notify(new ReminderNotification($patient, $event));
    }
}
