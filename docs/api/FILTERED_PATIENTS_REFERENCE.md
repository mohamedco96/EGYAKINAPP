# Filtered Patients API - Technical Reference

**Base URL**: `/api/v1` or `/api/v2`  
**Authentication**: Bearer Token (Required for all endpoints)

---

## 🔌 Endpoints

### 1A. Get Your Patients + Filter Conditions (Auth User)

**Endpoint**: `GET /api/v2/currentPatientsNew`

**Purpose**: Retrieve YOUR patients list and all available filter conditions in one call.

**Authentication**: Required

**Authorization**: Any authenticated user

**Query Parameters**:

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | No | `10` | Number of results per page (1-100) |

**Response**:
- `value` (boolean): Success indicator
- `verified` (boolean): Email verification status
- `patient_count` (string): Total patient count for user
- `score_value` (string): User's score
- `filter` (array): All available filter conditions
- `data` (object): Paginated patient list

**Response Codes**:
- `200`: Success
- `401`: Unauthorized
- `500`: Server error

---

### 1B. Get All Patients + Filter Conditions

**Endpoint**: `GET /api/v2/allPatientsNew`

**Purpose**: Retrieve ALL patients list and all available filter conditions in one call.

**Authentication**: Required

**Authorization**: Any authenticated user

**Query Parameters**:

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | No | `10` | Number of results per page (1-100) |

**Response**:
- `value` (boolean): Success indicator
- `filter` (array): All available filter conditions
- `data` (object): Paginated patient list
- `performance` (object): Execution metrics

**Response Codes**:
- `200`: Success
- `401`: Unauthorized
- `500`: Server error

---

### 2. Apply Filters

**Endpoint**: `POST /api/v2/patientFilters`

**Purpose**: Filter patients based on selected criteria.

**Authentication**: Required

**Authorization**: Any authenticated user (can filter your patients or all patients)

**Request Body Parameters**:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `{question_id}` | string/object | No | Filter value for specific question (see Filter IDs table) |
| `only_my_patients` | boolean | No | `true` = your patients, `false` = all patients (default: `false`) |
| `per_page` | integer | No | Results per page (default: `10`) |
| `page` | integer | No | Page number (default: `1`) |

**Response**:
- `value` (boolean): Success indicator
- `data` (object): Filtered paginated patient list

**Response Codes**:
- `200`: Success
- `400`: Invalid filter parameters
- `401`: Unauthorized
- `500`: Server error

**Cache Behavior**:
- Filter parameters are cached for 2 hours
- Cache is user-specific
- Used for export functionality

---

### 3. Export Filtered Patients

**Endpoint**: `POST /api/v2/exportFilteredPatients`

**Purpose**: Export the last filtered results to Excel.

**Authentication**: Required

**Authorization**: 
- Exporting your patients - Any authenticated user
- Exporting all patients - Admin role required

**Request Body**: None (uses cached filters)

**Response**: Excel file download

**Response Codes**:
- `200`: Success (file download)
- `400`: No cached filters found
- `401`: Unauthorized
- `403`: Forbidden (Non-admin trying to export all patients)
- `500`: Server error

**Requirements**:
- Must call `/patientFilters` first
- Cache expires after 2 hours
- Returns all matching records (no pagination)
- Admin role required when exporting all patients (`only_my_patients: false`)

---

## 🔧 Filter IDs & Types

### Standard Question Filters

| ID | Field | Type | Format | Example |
|----|-------|------|--------|---------|
| `1` | Patient Name | text | String | `"John Doe"` |
| `2` | Hospital | text | String | `"Hospital A"` |
| `4` | Gender | text | String | `"Male"` |
| `7` | Age Range | number_range | Object | `{"from": "25", "to": "45"}` |
| `8` | City | text | String | `"Cairo"` |
| `26` | Diagnosis | text | String | `"Diabetes"` |
| `79` | Treatment | text | String | `"Insulin"` |
| `82` | Complications | text | String | `"None"` |
| `86` | Blood Type | text | String | `"O+"` |
| `156` | Allergies | text | String | `"None"` |
| `162` | Medication | text | String | `"Metformin"` |
| `168` | Notes | text | String | `"Follow up"` |

### Special Status Filters

| ID | Field | Type | Format | Values |
|----|-------|------|--------|--------|
| `9901` | Final Submit Status | checkbox | String | `"Yes"` or `"No"` |
| `9902` | Outcome Status | checkbox | String | `"Yes"` or `"No"` |

### Date/Range Filters

| ID | Field | Type | Format | Example |
|----|-------|------|--------|---------|
| `7` | Age Range | number_range | Object | `{"from": "25", "to": "45"}` |
| `9903` | Registration Date | date_range | Object | `{"from": "2024-01-01", "to": "2024-12-31"}` |

---

## 📊 Parameter Details

### `only_my_patients` Parameter

**Type**: Boolean  
**Default**: `false`  
**Purpose**: Control data scope

**Values**:
- `true`: Returns only authenticated user's patients
- `false`: Returns all patients (admin feature)

**Use Cases**:
- "Your Patients" screen: `only_my_patients: true`
- "All Patients" screen: `only_my_patients: false`

---

### `per_page` Parameter

**Type**: Integer  
**Default**: `10`  
**Range**: 1-100  
**Purpose**: Control pagination size

**Recommendations**:
- Mobile: 10-20
- Desktop: 20-50
- Large screens: 50-100

---

### `page` Parameter

**Type**: Integer  
**Default**: `1`  
**Purpose**: Navigate through pages

**Usage**:
- First page: `page: 1`
- Next page: `page: 2`
- Use `last_page` from response to know total pages

---

### Text Filters (ID 1, 2, 4, 8, etc.)

**Type**: String  
**Format**: `"value"`  
**Matching**: Exact match (case-sensitive)

**Example**:
```json
{
  "1": "John",
  "2": "Hospital A"
}
```

---

### Number Range Filter (ID 7 - Age)

**Type**: Object  
**Format**: `{"from": "value", "to": "value"}`  
**Fields**:
- `from` (optional): Minimum age (string number)
- `to` (optional): Maximum age (string number)

**Examples**:
```json
// Both bounds
{"from": "25", "to": "45"}

// Only minimum
{"from": "18"}

// Only maximum
{"to": "65"}
```

**Validation**:
- Values must be numeric strings
- Both fields are optional
- At least one field should be provided

---

### Date Range Filter (ID 9903 - Registration Date)

**Type**: Object  
**Format**: `{"from": "YYYY-MM-DD", "to": "YYYY-MM-DD"}`  
**Fields**:
- `from` (optional): Start date
- `to` (optional): End date

**Date Format**: `YYYY-MM-DD` (e.g., `2024-01-15`)

**Examples**:
```json
// Both bounds
{"from": "2024-01-01", "to": "2024-12-31"}

// Only start date
{"from": "2024-01-01"}

// Only end date
{"to": "2024-12-31"}
```

**Validation**:
- Must be valid date format
- Invalid dates are silently ignored
- Both fields are optional

---

### Status Filters (ID 9901, 9902)

**Type**: String  
**Format**: `"Yes"` or `"No"`  
**Values**: Case-sensitive

**Behavior**:
- `"Yes"`: Filter for records with status = true
- `"No"`: Filter for records with status = false

**Example**:
```json
{
  "9901": "Yes",  // Final submit completed
  "9902": "No"    // Outcome not completed
}
```

---

## 📋 Response Structure

### Patient Object

```json
{
  "id": 123,
  "doctor_id": 456,
  "name": "Patient Name",
  "hospital": "Hospital Name",
  "updated_at": "2024-10-04T10:30:00Z",
  "doctor": {
    "id": 456,
    "name": "Doctor First Name",
    "lname": "Doctor Last Name",
    "image": "url_to_image",
    "syndicate_card": "card_number",
    "isSyndicateCardRequired": true
  },
  "answers": [
    {
      "id": 1,
      "patient_id": 123,
      "question_id": 1,
      "answer": "\"Answer Value\""
    }
  ],
  "sections": {
    "patient_id": 123,
    "submit_status": true,
    "outcome_status": false
  }
}
```

### Pagination Object

```json
{
  "current_page": 1,
  "data": [ /* array of patient objects */ ],
  "first_page_url": "...",
  "from": 1,
  "last_page": 5,
  "last_page_url": "...",
  "links": [...],
  "next_page_url": "...",
  "path": "...",
  "per_page": 20,
  "prev_page_url": null,
  "to": 20,
  "total": 100
}
```

---

## 🔐 Security

### Authentication
- All endpoints require Bearer token
- Token passed in `Authorization` header
- Format: `Authorization: Bearer {your_token}`

### Authorization

**Endpoints by Role:**

| Endpoint | Authenticated User | Admin |
|----------|-------------------|-------|
| `GET /api/v2/currentPatientsNew` | ✅ Your patients + filters | ✅ Your patients + filters |
| `GET /api/v2/allPatientsNew` | ✅ All patients + filters | ✅ All patients + filters |
| `POST /patientFilters` (only_my_patients: true) | ✅ Filter your patients | ✅ Filter your patients |
| `POST /patientFilters` (only_my_patients: false) | ✅ Filter all patients | ✅ Filter all patients |
| `POST /exportFilteredPatients` (your patients) | ✅ Export your patients | ✅ Export your patients |
| `POST /exportFilteredPatients` (all patients) | ❌ Forbidden | ✅ Export all patients |

**Role Checking:**
- Admin role is checked using `hasRole('Admin')` method
- Non-admin users receive `403 Forbidden` only when trying to **export all patients**
- GET and filter operations are available to all authenticated users

### Data Privacy
- Cache is user-specific
- No cross-user data leakage
- Filter parameters are isolated per user
- Admin actions are logged separately

---

## ⚠️ Error Handling

### Common Errors

**401 Unauthorized**
```json
{
  "message": "Unauthenticated."
}
```
**Solution**: Check Bearer token is valid

---

**403 Forbidden** (Non-admin exporting all patients)
```json
{
  "value": false,
  "message": "Access denied. Admin role required to export all patients."
}
```
**Solution**: 
- Use `only_my_patients: true` to export only your patients, or
- Contact admin for role upgrade to export all patients

---

**400 Bad Request** (Export without filters)
```json
{
  "value": false,
  "message": "No filter parameters found. Please apply filters first before exporting."
}
```
**Solution**: Call `/patientFilters` before exporting

---

**500 Server Error**
```json
{
  "value": false,
  "message": "Failed to retrieve filtered patients."
}
```
**Solution**: Check server logs

---

## 📝 Validation Rules

### Date Validation
- Format: `YYYY-MM-DD`
- Must be valid calendar date
- Invalid dates are ignored (query runs without that filter)

**Valid**: `2024-01-15`, `2024-12-31`  
**Invalid**: `2024-13-01`, `2024/01/15`, `01-01-2024`

---

### Age Validation
- Must be numeric string
- Non-numeric values are ignored
- Range bounds are inclusive

**Valid**: `"25"`, `"45"`  
**Invalid**: `25` (number), `"twenty-five"` (text)

---

### Status Validation
- Must be exact string match
- Case-sensitive
- Only `"Yes"` or `"No"` accepted

**Valid**: `"Yes"`, `"No"`  
**Invalid**: `"yes"`, `"YES"`, `true`, `false`

---

## 🚀 Performance

### Response Times
- Initial load (`/currentPatientsNew` or `/allPatientsNew`): < 100ms (optimized)
- Filter request (`/patientFilters`): < 200ms
- Export: Depends on result size

### Optimization Tips
1. Use reasonable page sizes (20-50)
2. Apply filters progressively
3. Cache filter options in frontend
4. Use `only_my_patients=true` when possible

### Database Queries
- Uses indexed columns
- Eager loading for related data
- Parameter binding for security
- Query builder optimization

---

## 💾 Cache Details

### Cache Duration
- **2 hours** for filter parameters
- **2 hours** for scope parameter
- User-specific cache keys

### Cache Keys
- `latest_filter_params_user_{user_id}`
- `latest_filter_scope_user_{user_id}`

### Cache Purpose
- Enable export without re-entering filters
- Temporary storage only
- Expires automatically

---

## 🔄 Workflow

### Typical Flow for Your Patients

1. **Initial Load**
   ```
   GET /api/v2/currentPatientsNew?per_page=20
   → Returns: your patients + filter options
   ```

2. **User Applies Filters**
   ```
   POST /api/v2/patientFilters
   Body: { "2": "Hospital A", "only_my_patients": true }
   → Returns: filtered patients
   ```

3. **Export Results** (Optional)
   ```
   POST /api/v2/exportFilteredPatients
   → Returns: Excel file with filtered patients
   ```

---

### Typical Flow for All Patients

1. **Initial Load**
   ```
   GET /api/v2/allPatientsNew?per_page=20
   → Returns: all patients + filter options
   ```

2. **User Applies Filters**
   ```
   POST /api/v2/patientFilters
   Body: { "2": "Hospital A", "only_my_patients": false }
   → Returns: filtered patients
   → Caches: filter parameters (2 hours)
   ```

3. **Admin Exports (Optional)** ⚠️ Admin Only
   ```
   POST /api/v2/exportFilteredPatients
   → Uses: cached filter parameters
   → Returns: Excel file (only for Admin role)
   ```

---

## 📍 Quick Reference

| Action | Endpoint | Method | Auth |
|--------|----------|--------|------|
| Get your patients + filters | `/currentPatientsNew` | GET | User |
| Get all patients + filters | `/allPatientsNew` | GET | User |
| Filter patients | `/patientFilters` | POST | User |
| Export your patients | `/exportFilteredPatients` | POST | User |
| Export all patients | `/exportFilteredPatients` | POST | **Admin** |

---

## 🆘 Troubleshooting

### Export Not Working
- Check if filters were applied within last 2 hours
- Re-apply filters and try again

### No Results Returned
- Check filter values are correct format
- Verify date format is `YYYY-MM-DD`
- Check age values are string numbers

### Slow Response
- Reduce page size
- Narrow filter criteria
- Use indexed filter fields (hospital, age, date)

---

**Last Updated**: October 2025  
**API Version**: V1 & V2

