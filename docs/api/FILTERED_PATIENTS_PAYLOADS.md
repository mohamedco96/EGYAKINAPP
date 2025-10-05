# Filtered Patients API - Payload Examples

**Base URL**: `/api/v1` or `/api/v2`  
**Auth**: Bearer Token Required

---

## 📋 Available Endpoints

### For All Authenticated Users
- `GET /api/v2/currentPatientsNew` - Get your patients + filter conditions
- `GET /api/v2/allPatientsNew` - Get all patients + filter conditions
- `POST /api/v2/patientFilters` - Filter patients (your patients or all patients)
- `POST /api/v2/exportFilteredPatients` - Export your filtered patients

### Admin Only
- `POST /api/v2/exportFilteredPatients` - Export **all** filtered patients (`only_my_patients: false`, requires Admin role)

---

## 📋 Request 1A: Get Your Patients + Filters (Auth User)

**Endpoint**: `GET /api/v2/currentPatientsNew`

**Headers**:
```
Authorization: Bearer {your_token}
```

**Query Parameters**:
```
?per_page=20
```

**Response**:
```json
{
  "value": true,
  "verified": true,
  "patient_count": "50",
  "score_value": "100",
  "filter": [
    {
      "id": 1,
      "condition": "Patient Name",
      "type": "text"
    },
    {
      "id": 7,
      "condition": "Age",
      "type": "number_range",
      "fields": {
        "from": "Age From",
        "to": "Age To"
      }
    }
  ],
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 123,
        "name": "Patient Name",
        "hospital": "Hospital Name"
      }
    ],
    "total": 50,
    "per_page": 20
  }
}
```

---

## 📋 Request 1B: Get All Patients + Filters

**Endpoint**: `GET /api/v2/allPatientsNew`

**Headers**:
```
Authorization: Bearer {your_token}
```

**Query Parameters**:
```
?per_page=20
```

**Response**:
```json
{
  "value": true,
  "filter": [
    {
      "id": 1,
      "condition": "Patient Name",
      "type": "text"
    },
    {
      "id": 7,
      "condition": "Age",
      "type": "number_range",
      "fields": {
        "from": "Age From",
        "to": "Age To"
      }
    },
    {
      "id": 9903,
      "condition": "Patient Registration Date",
      "type": "date_range",
      "fields": {
        "from": "Registration Date From",
        "to": "Registration Date To"
      }
    }
  ],
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 123,
        "name": "Patient Name",
        "hospital": "Hospital Name"
      }
    ],
    "total": 100,
    "per_page": 20
  }
}
```

---

## 📋 Request 2: Apply Filters

**Endpoint**: `POST /api/v2/patientFilters`

### Example 1: Filter by Hospital
```json
{
  "2": "Hospital A",
  "only_my_patients": true,
  "per_page": 20,
  "page": 1
}
```

---

### Example 2: Filter by Age Range
```json
{
  "7": {
    "from": "25",
    "to": "45"
  },
  "only_my_patients": true,
  "per_page": 20
}
```

---

### Example 3: Filter by Registration Date
```json
{
  "9903": {
    "from": "2024-01-01",
    "to": "2024-12-31"
  },
  "only_my_patients": true,
  "per_page": 20
}
```

---

### Example 4: Filter by Status
```json
{
  "9901": "Yes",
  "only_my_patients": true,
  "per_page": 20
}
```

---

### Example 5: Multiple Filters
```json
{
  "2": "Hospital A",
  "7": {
    "from": "30",
    "to": "50"
  },
  "9901": "Yes",
  "9903": {
    "from": "2024-01-01",
    "to": "2024-06-30"
  },
  "only_my_patients": true,
  "per_page": 20,
  "page": 1
}
```

---

### Example 6: All Patients
```json
{
  "2": "Hospital A",
  "only_my_patients": false,
  "per_page": 20
}
```

**Note**: Any authenticated user can filter all patients. Admin role is only required for exporting all patients.

---

### Example 7: Filter by Name
```json
{
  "1": "John",
  "only_my_patients": true,
  "per_page": 20
}
```

---

### Example 8: Filter by Gender
```json
{
  "4": "Male",
  "only_my_patients": true,
  "per_page": 20
}
```

---

### Example 9: Age Range Only (Minimum)
```json
{
  "7": {
    "from": "18"
  },
  "only_my_patients": true
}
```

---

### Example 10: Age Range Only (Maximum)
```json
{
  "7": {
    "to": "65"
  },
  "only_my_patients": true
}
```

---

## 📋 Request 3: Export Filtered Patients

**Endpoint**: `POST /api/v2/exportFilteredPatients`

**Headers**:
```
Authorization: Bearer {your_token}
```

**Body**: None (uses cached filters from last filter request)

**Response**: Excel file download

**Note**: Must call `/patientFilters` first within 2 hours

---

## 📄 Response Structure

### Filtered Patients Response
```json
{
  "value": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 123,
        "doctor_id": 456,
        "name": "Patient Name",
        "hospital": "Hospital Name",
        "updated_at": "2024-10-04T10:30:00Z",
        "doctor": {
          "id": 456,
          "name": "Doctor Name",
          "lname": "Doctor Last Name"
        },
        "sections": {
          "patient_id": 123,
          "submit_status": true,
          "outcome_status": false
        }
      }
    ],
    "total": 5,
    "per_page": 20,
    "last_page": 1
  }
}
```

---

## 🔑 Common Filter IDs

| ID | Field | Format |
|----|-------|--------|
| `1` | Patient Name | String: `"John"` |
| `2` | Hospital | String: `"Hospital A"` |
| `4` | Gender | String: `"Male"` |
| `7` | Age Range | Object: `{"from": "25", "to": "45"}` |
| `8` | City | String: `"Cairo"` |
| `9901` | Final Submit Status | String: `"Yes"` or `"No"` |
| `9902` | Outcome Status | String: `"Yes"` or `"No"` |
| `9903` | Registration Date | Object: `{"from": "2024-01-01", "to": "2024-12-31"}` |

---

## ⚠️ Important Notes

- Date format: `YYYY-MM-DD`
- Age format: String numbers (`"25"`, not `25`)
- Cache duration: 2 hours
- `only_my_patients`: `true` (your patients) or `false` (all patients)

