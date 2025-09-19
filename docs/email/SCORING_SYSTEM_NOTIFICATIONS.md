# Scoring System and Achievement Notifications

## Overview
The EGYAKIN platform automatically awards points to doctors for various activities and sends achievement notifications when they reach milestones of 50 points.

## Scoring System Implementation

### Point Values
- **Final Submit**: 4 points (when completing patient evaluation)
- **Add Outcome**: 1 point (when adding patient outcome)

### Notification Threshold
- **Achievement Milestone**: Every 50 points
- **Reset**: Threshold counter resets to 0 after notification is sent

## Automatic Notification Triggers

### 1. Final Submit Scoring
**File**: `app/Modules/Sections/Services/ScoringService.php`
**Trigger**: When doctor completes final patient submission
**Points Awarded**: 4 points
**API Endpoint**: `PUT /api/submitStatus/{patient_id}`

```php
public function processFinalSubmitScoring(int $patientId): void
{
    $score = Score::firstOrNew(['doctor_id' => $doctorId]);
    $score->score += 4; // FINAL_SUBMIT_SCORE
    $score->threshold += 4;
    
    // Send notification at 50-point milestones
    if ($score->threshold >= 50) {
        $user->notify(new ReachingSpecificPoints($score->score));
        $score->threshold = 0; // Reset threshold
    }
    
    $score->save();
}
```

### 2. Outcome Addition Scoring
**File**: `app/Modules/Patients/Services/PatientService.php`
**Trigger**: When doctor adds patient outcome (section_id = 8)
**Points Awarded**: 1 point
**API Endpoint**: Via patient section updates

```php
private function updateDoctorScore(int $doctorId, int $patientId): void
{
    $score = Score::firstOrNew(['doctor_id' => $doctorId]);
    $score->score += 1; // Add Outcome score
    $score->threshold += 1;
    
    // Send notification at 50-point milestones
    if ($score->threshold >= 50) {
        $user = User::find($doctorId);
        if ($user) {
            $user->notify(new ReachingSpecificPoints($score->score));
        }
        $score->threshold = 0; // Reset threshold
    }
    
    $score->save();
}
```

### 3. Legacy Backup Controllers
**Files**: 
- `app/Http/Controllers/bkp/OutcomeController.php`
- `app/Http/Controllers/bkp/SectionController.php`

These backup controllers also implement the same scoring logic for backward compatibility.

## Database Schema

### Scores Table
```sql
CREATE TABLE scores (
    id BIGINT PRIMARY KEY,
    doctor_id BIGINT NOT NULL,
    score INT NOT NULL DEFAULT 0,     -- Total accumulated points
    threshold INT NOT NULL DEFAULT 0, -- Points toward next notification
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id)
);
```

### Score History Table
```sql
CREATE TABLE score_histories (
    id BIGINT PRIMARY KEY,
    doctor_id BIGINT NOT NULL,
    score INT NOT NULL,           -- Points awarded for this action
    action VARCHAR(255),          -- Description of action
    patient_id BIGINT,           -- Associated patient (if applicable)
    timestamp TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id)
);
```

## Notification Flow

### Achievement Detection
1. **Action Performed**: Doctor completes qualifying activity
2. **Points Added**: System adds points to doctor's total score
3. **Threshold Check**: System checks if threshold ≥ 50 points
4. **Notification Sent**: `ReachingSpecificPoints` notification sent via Brevo API
5. **Threshold Reset**: Counter resets to 0 for next milestone

### Notification Content
- **Subject**: "Congrats from EGYAKIN"
- **Design**: Modern purple-blue gradient matching welcome mail
- **Content**: Achievement celebration with total score display
- **CTA**: "Continue Your Journey" button

## API Endpoints That Trigger Notifications

### 1. Final Submit
```http
PUT /api/submitStatus/{patient_id}
```
**Points**: +4
**Service**: `ScoringService::processFinalSubmitScoring()`

### 2. Patient Section Update (Outcome)
```http
PUT /api/patientsection/{section_id}/{patient_id}
```
**Points**: +1 (when section_id = 8)
**Service**: `PatientService::updateDoctorScore()`

### 3. Legacy Endpoints (Backup)
```http
POST /api/outcome (OutcomeController)
PUT /api/patient/{patient_id}/final-submit (SectionController)
```

## Testing the Notification System

### Manual Testing
```bash
# Test notification template
php artisan mail:test-all doctor@example.com --type=specific --specific=ReachingSpecificPoints --dry-run

# Test with actual email (uses quota)
php artisan mail:test-all doctor@example.com --type=specific --specific=ReachingSpecificPoints
```

### Database Testing
```sql
-- Check doctor's current score
SELECT * FROM scores WHERE doctor_id = 1;

-- View score history
SELECT * FROM score_histories WHERE doctor_id = 1 ORDER BY timestamp DESC;

-- Manually trigger notification (set threshold to 50)
UPDATE scores SET threshold = 50 WHERE doctor_id = 1;
```

## Scoring Examples

### Example 1: New Doctor
- **Initial**: 0 points, threshold = 0
- **Final Submit**: +4 points, threshold = 4
- **Add Outcome**: +1 point, threshold = 5
- **Continue**: Until threshold reaches 50
- **Notification**: Sent when threshold ≥ 50, then reset to 0

### Example 2: Achievement Milestone
- **Current**: 46 points, threshold = 46
- **Final Submit**: +4 points = 50 total, threshold = 50
- **Result**: 🏆 Achievement notification sent!
- **Reset**: 50 points total, threshold = 0
- **Next**: Need 50 more points for next notification

## Error Handling

### Scoring Service
- ✅ **Authentication Check**: Verifies authenticated user exists
- ✅ **Database Transactions**: Ensures data consistency
- ✅ **Error Logging**: Logs failures for debugging
- ✅ **Graceful Degradation**: Continues operation if notification fails

### Notification Delivery
- ✅ **User Validation**: Checks user exists before sending
- ✅ **Brevo API**: Reliable email delivery via Brevo
- ✅ **Fallback**: System continues if email fails
- ✅ **Logging**: Records notification attempts

## Configuration

### Constants
```php
// ScoringService.php
private const FINAL_SUBMIT_SCORE = 4;
private const NOTIFICATION_THRESHOLD = 50;

// Other services
$incrementAmount = 1; // Add Outcome score
$threshold = 50;      // Notification threshold
```

### Customization
To modify point values or thresholds:
1. Update constants in `ScoringService.php`
2. Update hardcoded values in other services
3. Consider database migration if changing existing scores

## Monitoring and Analytics

### Score Tracking
- **Total Points**: Cumulative doctor achievements
- **Activity History**: Detailed log of point-earning actions
- **Notification History**: Track achievement milestones

### Performance Metrics
- **Notification Delivery**: Success/failure rates
- **User Engagement**: Response to achievement notifications
- **System Load**: Impact of scoring calculations

## Benefits

### For Doctors
- 🏆 **Recognition**: Celebrates contributions and achievements
- 📈 **Motivation**: Encourages continued platform engagement
- 🎯 **Progress Tracking**: Clear visibility of accumulated points
- 🎉 **Milestone Celebration**: Professional achievement notifications

### For EGYAKIN
- 📊 **User Engagement**: Gamification increases platform usage
- 🔄 **Retention**: Achievement system encourages return visits
- 📈 **Quality Metrics**: Points reflect meaningful platform contributions
- 💎 **Professional Image**: High-quality notification design

The scoring system automatically recognizes doctor contributions and celebrates achievements through professional, engaging email notifications that maintain EGYAKIN's premium brand standards.
