# Consultations Access Control Enhancement

## Overview
Enhanced both AI consultations and regular consultations systems to allow all authenticated users to view consultations while restricting creation, modification, and management to only admins and patient owners.

## Systems Updated

### 1. AI Consultations (ChatService)
- **Module**: `app/Modules/Chat/Services/ChatService.php`
- **Endpoints**: 
  - `POST /api/v1/AIconsultation/{patientId}` (Create AI consultation)
  - `GET /api/v1/AIconsultation-history/{patientId}` (View AI consultation history)

### 2. Regular Consultations (ConsultationService)
- **Module**: `app/Modules/Consultations/Services/ConsultationService.php`
- **Endpoints**:
  - `POST /api/v1/consultations` (Create consultation)
  - `GET /api/v1/consultations/{id}` (View consultation details)
  - `GET /api/v1/consultations/sent` (View sent consultations)
  - `GET /api/v1/consultations/received` (View received consultations)
  - `PUT /api/v1/consultations/{id}` (Update consultation reply)
  - `POST /api/v1/consultations/{id}/add-doctors` (Add doctors to consultation)
  - `PUT /api/v1/consultations/{id}/toggle-status` (Toggle consultation status)
  - `DELETE /api/v1/consultations/{consultationId}/doctors/{doctorId}` (Remove doctor)

## Changes Made

### 1. Centralized Authorization Logic

Added authorization helper method to both services:

```php
/**
 * Check if the current user can modify consultations for a patient.
 * Only admins and patient owners can create/modify consultations.
 */
private function canModifyConsultations(Patients $patient): bool
{
    $user = Auth::user();
    
    // Check if user is admin
    if ($user->hasRole('Admin')) {
        return true;
    }
    
    // Check if user is the patient owner
    if ($patient->doctor_id === Auth::id()) {
        return true;
    }
    
    return false;
}
```

### 2. AI Consultations Access Control

#### View Access (getConsultationHistory)
```php
/**
 * Get consultation history for a patient.
 * All users can view consultation history.
 */
public function getConsultationHistory(int $patientId): array
{
    // Check if patient exists
    $patient = Patients::find($patientId);
    if (!$patient) {
        return ['success' => false, 'message' => 'Patient not found'];
    }

    // Allow all authenticated users to view consultation history
    Log::info('AI consultation history accessed', [
        'viewer_id' => Auth::id(),
        'patient_id' => $patientId,
        'patient_owner' => $patient->doctor_id,
    ]);
    
    // Fetch and return consultation history...
}
```

#### Create Access (sendConsultation)
```php
/**
 * Send consultation request for a patient
 * Only admins and patient owners can create consultations.
 */
public function sendConsultation(int $patientId): array
{
    $patient = Patients::with(['doctor', 'status', 'answers'])->findOrFail($patientId);

    // Check if user can modify consultations (admin or patient owner)
    if (!$this->canModifyConsultations($patient)) {
        Log::warning('Unauthorized AI consultation attempt', [
            'doctor_id' => Auth::id(),
            'patient_id' => $patientId,
            'patient_owner' => $patient->doctor_id,
            'user_roles' => Auth::user()->getRoleNames(),
        ]);

        return [
            'success' => false,
            'data' => ['value' => false, 'message' => __('api.unauthorized_action')],
            'status_code' => 403,
        ];
    }
    
    // Process AI consultation...
}
```

### 3. Regular Consultations Access Control

#### View Access (getConsultationDetails)
```php
/**
 * Get detailed consultation information.
 * All users can view consultation details.
 */
public function getConsultationDetails(int $id): array
{
    // Allow all authenticated users to view consultation details
    Log::info('Consultation details accessed', [
        'consultation_id' => $id,
        'viewer_id' => Auth::id(),
    ]);

    $consultations = Consultation::where('id', $id)
        ->with([/* all relationships */])
        ->get();
    
    // Return consultation details...
}
```

#### Create Access (createConsultation)
```php
/**
 * Create a new consultation with associated doctors.
 * Only admins and patient owners can create consultations.
 */
public function createConsultation(array $data): array
{
    return DB::transaction(function () use ($data) {
        $patient = Patients::find($data['patient_id']);
        if (!$patient) {
            return ['value' => false, 'message' => 'Patient not found.'];
        }

        // Check if user can modify consultations (admin or patient owner)
        if (!$this->canModifyConsultations($patient)) {
            Log::warning('Unauthorized consultation creation attempt', [
                'doctor_id' => Auth::id(),
                'patient_id' => $data['patient_id'],
                'patient_owner' => $patient->doctor_id,
                'user_roles' => Auth::user()->getRoleNames(),
            ]);

            return ['value' => false, 'message' => __('api.unauthorized_action')];
        }
        
        // Create consultation...
    });
}
```

#### Management Access (addDoctorsToConsultation, toggleConsultationStatus, removeDoctorFromConsultation)
All consultation management methods now use the centralized authorization:

```php
// Check if user can modify consultations (admin or consultation owner)
if (!$this->canModifyConsultations($consultation->patient)) {
    Log::warning('Unauthorized attempt to modify consultation', [
        'consultation_id' => $consultationId,
        'doctor_id' => Auth::id(),
        'consultation_owner' => $consultation->doctor_id,
        'user_roles' => Auth::user()->getRoleNames(),
    ]);

    return [
        'value' => false,
        'message' => __('api.unauthorized_action'),
    ];
}
```

## Authorization Matrix

### AI Consultations

| User Type | View History | Create Consultation |
|-----------|-------------|-------------------|
| **Admin** | ✅ Yes | ✅ Yes |
| **Patient Owner** | ✅ Yes | ✅ Yes |
| **Other Doctors** | ✅ Yes | ❌ No |

### Regular Consultations

| User Type | View Details | Create | Add Doctors | Toggle Status | Remove Doctors | Reply |
|-----------|-------------|--------|-------------|---------------|----------------|-------|
| **Admin** | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes* |
| **Patient Owner** | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes* |
| **Other Doctors** | ✅ Yes | ❌ No | ❌ No | ❌ No | ❌ No | ✅ Yes* |

*Reply access is for doctors who are part of the consultation

## API Responses

### Successful View Access (All Users)
```json
{
  "value": true,
  "data": [
    {
      "id": 1,
      "patient_id": 732,
      "question": "Patient consultation request...",
      "response": "AI generated response...",
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "message": "Consultation history fetched successfully."
}
```

### Successful Create Access (Admins & Patient Owners)
```json
{
  "success": true,
  "data": {
    "value": true,
    "message": "Consultation sent successfully",
    "consultation_id": 123,
    "trial_count": 2
  },
  "status_code": 201
}
```

### Unauthorized Access Attempt
```json
{
  "value": false,
  "message": "You are not authorized to perform this action."
}
```

## Enhanced Logging

### View Access Logging
```php
// AI Consultations
Log::info('AI consultation history accessed', [
    'viewer_id' => Auth::id(),
    'patient_id' => $patientId,
    'patient_owner' => $patient->doctor_id,
]);

// Regular Consultations
Log::info('Consultation details accessed', [
    'consultation_id' => $id,
    'viewer_id' => Auth::id(),
]);
```

### Unauthorized Access Logging
```php
Log::warning('Unauthorized consultation creation attempt', [
    'doctor_id' => Auth::id(),
    'patient_id' => $patientId,
    'patient_owner' => $patient->doctor_id,
    'user_roles' => Auth::user()->getRoleNames(),
]);
```

## Security Features

### 1. Role-Based Access Control
- Utilizes Spatie Laravel Permission package
- Checks for 'Admin' role explicitly
- Falls back to patient/consultation ownership check

### 2. Comprehensive Logging
- All access attempts logged with detailed context
- Includes user roles, ownership information, and action type
- Separate log levels for successful vs unauthorized access

### 3. Centralized Authorization
- Single method handles all modification authorization
- Consistent logic across all consultation operations
- Easy to maintain and modify authorization rules

### 4. Trial System Protection (AI Consultations)
- Trial count validation remains for AI consultations
- Only authorized users can consume trials
- Prevents unauthorized trial usage

## Use Cases Enabled

### 1. Medical Collaboration
- Doctors can view consultations from colleagues for better patient care
- Cross-referencing of treatment approaches
- Learning from AI-generated recommendations

### 2. Medical Education
- Medical students can view consultation patterns
- Learning from real-world consultation examples
- Understanding AI-assisted diagnosis approaches

### 3. Quality Assurance
- Medical supervisors can review consultation quality
- Audit trail for consultation decisions
- Performance monitoring and improvement

### 4. Administrative Oversight
- Admins can manage consultations when necessary
- System-wide consultation monitoring
- Emergency access for critical situations

## Testing Scenarios

### Test Case 1: Admin Access (Full Access)
```bash
# Admin should have full access to all operations
curl -H "Authorization: Bearer {admin_token}" GET /api/v1/AIconsultation-history/732
curl -H "Authorization: Bearer {admin_token}" POST /api/v1/AIconsultation/732
curl -H "Authorization: Bearer {admin_token}" GET /api/v1/consultations/123
curl -H "Authorization: Bearer {admin_token}" POST /api/v1/consultations
```

### Test Case 2: Patient Owner Access (Full Access)
```bash
# Patient owner should have full access
curl -H "Authorization: Bearer {owner_token}" GET /api/v1/AIconsultation-history/732
curl -H "Authorization: Bearer {owner_token}" POST /api/v1/AIconsultation/732
curl -H "Authorization: Bearer {owner_token}" GET /api/v1/consultations/123
curl -H "Authorization: Bearer {owner_token}" POST /api/v1/consultations
```

### Test Case 3: Other Doctor Access (View Only)
```bash
# Other doctors should only be able to view
curl -H "Authorization: Bearer {other_doctor_token}" GET /api/v1/AIconsultation-history/732  # ✅ Success
curl -H "Authorization: Bearer {other_doctor_token}" POST /api/v1/AIconsultation/732        # ❌ 403 Forbidden
curl -H "Authorization: Bearer {other_doctor_token}" GET /api/v1/consultations/123         # ✅ Success
curl -H "Authorization: Bearer {other_doctor_token}" POST /api/v1/consultations            # ❌ 403 Forbidden
```

### Test Case 4: Consultation Member Reply Access
```bash
# Doctors who are part of a consultation can reply
curl -H "Authorization: Bearer {consultation_member_token}" POST /api/v1/consultations/123/replies  # ✅ Success
curl -H "Authorization: Bearer {consultation_member_token}" PUT /api/v1/consultations/123           # ✅ Success
```

## Migration Impact

### Backward Compatibility
- Existing API endpoints remain unchanged
- Response structures are consistent
- Only authorization logic has been modified

### Database Changes
- No database schema changes required
- Existing consultation data remains intact
- No migration scripts needed

### Trial System (AI Consultations)
- Trial counting logic preserved
- Only authorized users consume trials
- Trial limits still apply per doctor

## Benefits

### 1. Enhanced Medical Collaboration
- Cross-team consultation visibility
- Better patient care through knowledge sharing
- Improved medical decision-making processes

### 2. Maintained Security
- Sensitive operations require proper authorization
- Admin oversight capabilities preserved
- Patient data ownership respected

### 3. Improved Transparency
- All medical staff can review consultation approaches
- Better audit trail for medical decisions
- Enhanced quality assurance capabilities

### 4. Educational Value
- Medical students can learn from real consultations
- AI consultation patterns become learning resources
- Best practices sharing across the platform

### 5. Flexible Access Control
- Easy to modify authorization rules in the future
- Centralized authorization logic
- Role-based permissions system integration

### 6. Cost Efficiency (AI Consultations)
- Trial system prevents unauthorized AI usage
- Resource consumption tracking maintained
- Fair usage policies enforced

## Error Handling

### 1. Patient Not Found
```json
{
  "success": false,
  "data": {
    "value": false,
    "message": "Patient not found"
  },
  "status_code": 404
}
```

### 2. Consultation Not Found
```json
{
  "value": false,
  "message": "Consultation not found."
}
```

### 3. Unauthorized Access
```json
{
  "value": false,
  "message": "You are not authorized to perform this action."
}
```

### 4. Trial Limit Exceeded (AI Consultations)
```json
{
  "success": false,
  "data": {
    "value": false,
    "message": "No trials left for this month"
  },
  "status_code": 403
}
```

This comprehensive enhancement provides a balanced approach to consultation access, promoting medical collaboration while maintaining appropriate security controls and preserving the existing trial system for AI consultations.
