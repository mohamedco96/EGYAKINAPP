# Recommendations Access Control Enhancement

## Overview
Enhanced the recommendations system to allow all authenticated users to view recommendations while restricting creation, modification, and deletion to only admins and patient owners.

## Changes Made

### 1. Updated Access Control Logic

#### View Recommendations (GET)
- **Before**: Only patient owners could view recommendations
- **After**: All authenticated users can view recommendations
- **Endpoint**: `GET /api/v1/recommendations/{patient_id}`

#### Modify Recommendations (POST/PUT/DELETE)
- **Before**: Only patient owners could modify recommendations
- **After**: Only admins and patient owners can modify recommendations
- **Endpoints**: 
  - `POST /api/v1/recommendations/{patient_id}` (Create)
  - `PUT /api/v1/recommendations/{patient_id}` (Update)
  - `DELETE /api/v1/recommendations/{patient_id}` (Delete)

### 2. Authorization Helper Method

Added a new private method `canModifyRecommendations()` to centralize authorization logic:

```php
private function canModifyRecommendations(Patients $patient): bool
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

### 3. Updated Service Methods

#### getPatientRecommendations()
```php
/**
 * Get all recommendations for a patient.
 * All users can view recommendations.
 */
public function getPatientRecommendations(int $patientId): array
{
    // Removed ownership check - all authenticated users can view
    $patient = Patients::findOrFail($patientId);
    $recommendations = $patient->recommendations()->get();
    
    // Enhanced logging with viewer information
    Log::info('Successfully fetched recommendations', [
        'patient_id' => $patientId, 
        'count' => $recommendations->count(),
        'viewer_id' => Auth::id(),
        'patient_owner' => $patient->doctor_id,
    ]);
}
```

#### createRecommendations()
```php
/**
 * Create new recommendations for a patient.
 * Only admins and patient owners can create recommendations.
 */
public function createRecommendations(int $patientId, array $recommendations): array
{
    $patient = Patients::findOrFail($patientId);
    
    // Use centralized authorization check
    if (!$this->canModifyRecommendations($patient)) {
        return [
            'value' => false,
            'message' => __('api.unauthorized_action'),
        ];
    }
}
```

#### updateRecommendations() & deleteRecommendations()
Similar authorization logic applied to update and delete operations.

## API Behavior

### For All Users (View Access)
```bash
GET /api/v1/recommendations/732
```

**Response (Success):**
```json
{
  "value": true,
  "data": [
    {
      "id": 1,
      "patient_id": 732,
      "type": "rec",
      "content": "Take medication as prescribed",
      "dose_name": "Aspirin",
      "dose": "100mg",
      "route": "Oral",
      "frequency": "Once daily",
      "duration": "30 days"
    }
  ],
  "message": "Recommendations fetched successfully."
}
```

### For Admins and Patient Owners (Modify Access)
```bash
POST /api/v1/recommendations/732
PUT /api/v1/recommendations/732
DELETE /api/v1/recommendations/732
```

**Response (Success):**
```json
{
  "value": true,
  "data": [...],
  "message": "Recommendations created/updated/deleted successfully."
}
```

### For Unauthorized Users (Modify Attempt)
```bash
POST /api/v1/recommendations/732
```

**Response (Error):**
```json
{
  "value": false,
  "data": null,
  "message": "You are not authorized to perform this action."
}
```

## Authorization Matrix

| User Type | View Recommendations | Create | Update | Delete |
|-----------|---------------------|--------|--------|--------|
| **Admin** | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| **Patient Owner** | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| **Other Doctors** | ✅ Yes | ❌ No | ❌ No | ❌ No |

## Enhanced Logging

### View Access Logging
```php
Log::info('Successfully fetched recommendations', [
    'patient_id' => $patientId, 
    'count' => $recommendations->count(),
    'viewer_id' => Auth::id(),
    'patient_owner' => $patient->doctor_id,
]);
```

### Unauthorized Access Logging
```php
Log::warning('Unauthorized recommendation creation attempt', [
    'doctor_id' => Auth::id(),
    'patient_id' => $patientId,
    'patient_owner' => $patient->doctor_id,
    'user_roles' => Auth::user()->getRoleNames(),
]);
```

## Localization Support

### English
- `unauthorized_action` → "You are not authorized to perform this action."

### Arabic
- `unauthorized_action` → "غير مخول لك القيام بهذا الإجراء."

## Security Features

### 1. Role-Based Access Control
- Utilizes Spatie Laravel Permission package
- Checks for 'Admin' role explicitly
- Falls back to patient ownership check

### 2. Comprehensive Logging
- All access attempts are logged with detailed context
- Includes user roles, patient ownership, and action type
- Separate log levels for successful vs unauthorized access

### 3. Centralized Authorization
- Single method handles all modification authorization
- Consistent logic across create, update, and delete operations
- Easy to maintain and modify authorization rules

## Use Cases

### 1. Medical Consultation
Doctors can view recommendations from other doctors for better patient care coordination.

### 2. Medical Education
Medical students or residents can view recommendations for learning purposes.

### 3. Quality Assurance
Medical supervisors can review recommendations without being able to modify them.

### 4. Administrative Oversight
Admins can manage recommendations across all patients when necessary.

## Testing Scenarios

### Test Case 1: Admin Access
```bash
# Admin user should have full access
curl -H "Authorization: Bearer {admin_token}" GET /api/v1/recommendations/732
curl -H "Authorization: Bearer {admin_token}" POST /api/v1/recommendations/732
curl -H "Authorization: Bearer {admin_token}" PUT /api/v1/recommendations/732
curl -H "Authorization: Bearer {admin_token}" DELETE /api/v1/recommendations/732
```

### Test Case 2: Patient Owner Access
```bash
# Patient owner should have full access
curl -H "Authorization: Bearer {owner_token}" GET /api/v1/recommendations/732
curl -H "Authorization: Bearer {owner_token}" POST /api/v1/recommendations/732
curl -H "Authorization: Bearer {owner_token}" PUT /api/v1/recommendations/732
curl -H "Authorization: Bearer {owner_token}" DELETE /api/v1/recommendations/732
```

### Test Case 3: Other Doctor Access
```bash
# Other doctors should only be able to view
curl -H "Authorization: Bearer {other_doctor_token}" GET /api/v1/recommendations/732  # ✅ Success
curl -H "Authorization: Bearer {other_doctor_token}" POST /api/v1/recommendations/732 # ❌ 403 Forbidden
curl -H "Authorization: Bearer {other_doctor_token}" PUT /api/v1/recommendations/732  # ❌ 403 Forbidden
curl -H "Authorization: Bearer {other_doctor_token}" DELETE /api/v1/recommendations/732 # ❌ 403 Forbidden
```

## Migration Impact

### Backward Compatibility
- Existing API endpoints remain unchanged
- Response structure is consistent
- Only authorization logic has been modified

### Database Changes
- No database schema changes required
- Existing data remains intact
- No migration scripts needed

## Benefits

### 1. Enhanced Collaboration
- Doctors can learn from each other's recommendations
- Better patient care through knowledge sharing
- Improved medical decision-making

### 2. Maintained Security
- Sensitive operations still require proper authorization
- Admin oversight capabilities preserved
- Patient data ownership respected

### 3. Improved Transparency
- All medical staff can review treatment recommendations
- Better audit trail for medical decisions
- Enhanced quality assurance capabilities

### 4. Flexible Access Control
- Easy to modify authorization rules in the future
- Centralized authorization logic
- Role-based permissions system integration

This enhancement provides a balanced approach to recommendations access, promoting collaboration while maintaining appropriate security controls.
