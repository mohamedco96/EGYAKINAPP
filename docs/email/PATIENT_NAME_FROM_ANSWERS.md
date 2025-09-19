# Patient Name from Answers Table

## Overview
The reminder system now retrieves patient names from the `answers` table where `question_id = 1`, instead of using a generic patient object.

## Implementation Details

### Database Structure
The system uses the following tables:
- `patient_statuses` - Contains submit/outcome status records
- `answers` - Contains patient answers to questions
- `questions` - Contains the question definitions

### Patient Name Retrieval
Patient names are retrieved from:
```sql
SELECT answer FROM answers 
WHERE patient_id = ? AND question_id = 1
```

### Question ID 1
- **Purpose**: Stores the patient's name
- **Location**: `answers.answer` field
- **Format**: Can be stored as plain text or JSON array

## Code Implementation

### ReminderNotification Updates

#### New Property
```php
protected $patientName;
```

#### Constructor Enhancement
```php
public function __construct($patient, $events)
{
    // ... existing code ...
    
    // Get patient name from answers table where question_id = 1
    $this->patientName = $this->getPatientNameFromAnswers();
}
```

#### Patient Name Retrieval Method
```php
private function getPatientNameFromAnswers(): string
{
    try {
        // Get patient ID from patient object or events object
        $patientId = $this->patient->id ?? $this->events->patient_id ?? null;
        
        if (!$patientId) {
            return 'Unknown Patient';
        }

        // Get patient name from answers where question_id = 1
        $answer = Answers::where('patient_id', $patientId)
            ->where('question_id', 1)
            ->first();

        if ($answer && !empty($answer->answer)) {
            // Handle different answer formats
            if (is_array($answer->answer)) {
                return $answer->answer[0] ?? 'Unknown Patient';
            } elseif (is_string($answer->answer)) {
                $decoded = json_decode($answer->answer, true);
                if (is_array($decoded)) {
                    return $decoded[0] ?? $answer->answer;
                }
                return $answer->answer;
            }
        }

        return 'Unknown Patient';
    } catch (\Exception $e) {
        \Log::warning('Failed to get patient name from answers', [
            'patient_id' => $patientId ?? null,
            'error' => $e->getMessage()
        ]);
        return 'Unknown Patient';
    }
}
```

### Email Template Updates

#### HTML Content
```php
// Before
<span class="info-value">'.htmlspecialchars($this->patient->name).'</span>

// After  
<span class="info-value">'.htmlspecialchars($this->patientName).'</span>
```

#### Text Content
```php
// Before
- Patient Name: '.$this->patient->name.'

// After
- Patient Name: '.$this->patientName.'
```

## Testing Updates

### Test Data Creation
The test command now creates realistic test data:

```php
// Create patient name answer (question_id = 1)
$patientName = 'Test Patient ' . $patient->id;
Answers::updateOrCreate(
    [
        'patient_id' => $patient->id,
        'question_id' => 1,
        'doctor_id' => $doctor->id,
    ],
    [
        'section_id' => 1,
        'answer' => $patientName,
        'type' => 'text',
    ]
);
```

### Test Commands
```bash
# Test with created patient name data
php artisan reminder:test --hours=1 --create-test-data --dry-run

# Send test email with patient name from answers
php artisan reminder:test --hours=1 --create-test-data --email=your@email.com
```

## Data Format Handling

The system handles multiple answer formats:

### Plain Text
```
answer: "John Doe"
```

### JSON String
```
answer: "[\"John Doe\"]"
```

### Array (after casting)
```
answer: ["John Doe"]
```

## Error Handling

### Fallback Strategy
1. Try to get patient ID from patient object
2. If not available, try from events object
3. Query answers table for question_id = 1
4. Handle different data formats (array, JSON, string)
5. Return "Unknown Patient" if anything fails
6. Log warnings for debugging

### Logging
```php
\Log::warning('Failed to get patient name from answers', [
    'patient_id' => $patientId ?? null,
    'error' => $e->getMessage()
]);
```

## Benefits

### Accurate Patient Names
- ✅ Uses actual patient names from form submissions
- ✅ Handles different data formats automatically
- ✅ Provides fallback for missing data

### Robust Error Handling
- ✅ Graceful degradation on errors
- ✅ Logging for debugging issues
- ✅ Never fails the entire notification

### Testing Support
- ✅ Creates realistic test data
- ✅ Validates name retrieval logic
- ✅ Supports multiple testing scenarios

## Usage in Reminder Emails

When doctors receive reminder emails, they now see:

```
📋 Patient Information:
👤 Patient Name: John Doe Smith
⏰ Added Since: 2025-09-19 10:30:00
📊 Status: Outcome Pending
```

Instead of generic patient identifiers, making the reminders more meaningful and actionable.
