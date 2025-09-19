# Patient Outcome Reminder System

## Overview
The Patient Outcome Reminder System automatically sends reminder emails to doctors when patients have submitted status but are missing outcome status after 72 hours.

## Requirements
- Send reminders for patients with `submit_status = true` in `patient_statuses` table
- Only send if there's NO corresponding `outcome_status = true` record
- Only send reminders after 72 hours from the `submit_status` creation
- Prevent spam by not sending more than once per week per patient
- Track reminder sending to avoid duplicates

## Database Structure

### patient_statuses Table
```sql
- id (bigint, primary key)
- doctor_id (bigint, foreign key to users.id)
- patient_id (bigint, foreign key to patients.id)
- key (string) - Values: 'submit_status', 'outcome_status', 'outcome_reminder_sent'
- status (boolean) - true/false
- created_at (timestamp)
- updated_at (timestamp)
```

### Key Values
- `submit_status`: Indicates patient case was submitted
- `outcome_status`: Indicates patient outcome was provided
- `outcome_reminder_sent`: Tracks when reminder was sent (prevents spam)

## System Logic

### 1. Finding Patients Needing Reminders
```php
// Get submit_status records older than 72 hours
PatientStatus::where('key', 'submit_status')
    ->where('status', true)
    ->where('created_at', '<=', Carbon::now()->subHours(72))
    ->get()

// Filter out those with outcome_status
foreach ($submitStatuses as $submitStatus) {
    $hasOutcome = PatientStatus::where('patient_id', $submitStatus->patient_id)
        ->where('doctor_id', $submitStatus->doctor_id)
        ->where('key', 'outcome_status')
        ->where('status', true)
        ->exists();
    
    if (!$hasOutcome) {
        // This patient needs a reminder
    }
}
```

### 2. Spam Prevention
```php
// Check if reminder was sent recently (within 7 days)
$recentReminder = PatientStatus::where('patient_id', $patient_id)
    ->where('doctor_id', $doctor_id)
    ->where('key', 'outcome_reminder_sent')
    ->where('status', true)
    ->where('created_at', '>=', Carbon::now()->subDays(7))
    ->first();

if (!$recentReminder) {
    // Send reminder and create tracking record
}
```

### 3. Tracking Reminder Sends
```php
// After sending reminder, create tracking record
PatientStatus::create([
    'doctor_id' => $doctor_id,
    'patient_id' => $patient_id,
    'key' => 'outcome_reminder_sent',
    'status' => true,
]);
```

## Laravel Scheduler Configuration

### Frequency
- Runs every 6 hours: `->everySixHours()`
- Prevents overlapping: `->withoutOverlapping(60)`
- Runs in background: `->runInBackground()`
- Logs to: `storage/logs/reminder_emails.log`

### Command Signature
```bash
# Run with actual email sending
php artisan reminder:send

# Run in dry-run mode (no emails sent)
php artisan reminder:send --dry-run
```

## Usage Examples

### Manual Testing (Dry Run)
```bash
php artisan reminder:send --dry-run
```

### Manual Execution (Send Emails)
```bash
php artisan reminder:send
```

### Check Logs
```bash
tail -f storage/logs/reminder_emails.log
tail -f storage/logs/laravel.log
```

## Email Template
The system uses `ReminderNotification` which includes:
- Modern purple-blue gradient design
- Patient information display
- Urgent notice section
- Call-to-action button
- Professional footer

## Error Handling
- Database connection errors are logged
- Individual patient processing errors don't stop the entire job
- Comprehensive logging for debugging
- Graceful handling of missing doctors/patients

## Monitoring
- Success/failure counts in command output
- Detailed logging with timestamps
- Laravel scheduler success/failure callbacks
- Email sending tracking in database

## Security Features
- Validates doctor and patient existence
- Prevents duplicate reminders (spam protection)
- Secure notification system
- Proper database relationships and constraints

## Performance Considerations
- Efficient database queries with proper indexing
- Batch processing of reminders
- Background execution in scheduler
- Timeout protection (60 minutes max)
- Memory-efficient filtering using collections

## Testing
- Dry-run mode for safe testing
- Comprehensive error reporting
- Database relationship validation
- Email template testing integration
