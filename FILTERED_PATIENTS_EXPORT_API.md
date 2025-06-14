# Filtered Patients Export API

## Overview

This document describes the API endpoint for exporting filtered patients as a CSV file. The endpoint automatically uses the filter criteria from the most recent call to `filteredPatients` for the authenticated user.

## Endpoint

**POST** `/api/exportFilteredPatients`

## Authentication

This endpoint requires authentication using Laravel Sanctum tokens.

## Workflow

1. **Get Filter Conditions**: First, call `GET /api/patientFilters` to get available filter conditions
2. **Apply Filters**: Use `POST /api/patientFilters` with your desired filter parameters
3. **Export Data**: Call `POST /api/exportFilteredPatients` (no parameters needed - uses cached filters)

## Request Parameters

**No parameters required** - The endpoint automatically uses the filter parameters from the most recent `filteredPatients` call for the authenticated user.

## Example Request

```bash
# Step 1: Apply filters first
curl -X POST \
  http://your-domain.com/api/patientFilters \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "1": "John Doe",
    "2": "General Hospital",
    "9901": "Yes"
  }'

# Step 2: Export using cached filters
curl -X POST \
  http://your-domain.com/api/exportFilteredPatients \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json'
```

## Response

### Success Response (200)

```json
{
  "value": true,
  "message": "Export completed successfully",
  "file_url": "http://your-domain.com/storage/exports/filtered_patients_export_3_filters_2025-06-14_10-30-15.xlsx",
  "patient_count": 25,
  "filter_count": 3,
  "cache_key": "filtered_patients_export_abc123_1234"
}
```

### No Cached Filters (400)

```json
{
  "value": false,
  "message": "No recent filter criteria found. Please apply filters first using the filteredPatients endpoint."
}
```

### No Data Found (404)

```json
{
  "value": false,
  "message": "No patients found matching the cached filter criteria."
}
```

### Error Response (500)

```json
{
  "value": false,
  "message": "Failed to export data: [error details]"
}
```

## Features

### Automatic Filter Retrieval

The API automatically uses filter parameters from the most recent `filteredPatients` call:

- **User-Specific**: Each user's filter criteria are cached separately
- **Auto-Sync**: No need to manually send filter parameters to export
- **24-Hour Cache**: Filter criteria are cached for 24 hours
- **Error Handling**: Clear error message if no recent filters are found

### Caching

The API uses caching to track filter parameters and export results:

- **Filter Parameters**: Cached for 24 hours with key `{cache_key}_filters`
- **Export Results**: Cached for 24 hours with key `{cache_key}_result`
- **Cache Key Format**: `filtered_patients_export_{filter_hash}_{user_id}`

### CSV Structure

The exported CSV file contains the following columns:

1. **Patient ID**
2. **Doctor ID**
3. **Doctor Name**
4. **Patient Name**
5. **Hospital**
6. **Submit Status** (Yes/No)
7. **Outcome Status** (Yes/No)
8. **Last Updated**
9. **Question Columns**: One column per question from the database, ordered by question ID

### File Management

- Files are stored in `storage/app/public/exports/`
- Filename format: `filtered_patients_export_{filter_count}_filters_{timestamp}.xlsx`
- Files are publicly accessible via the returned URL

## Technical Implementation

### Dependencies

- `maatwebsite/excel` package for Excel file generation
- Laravel Cache for parameter tracking
- Laravel Storage for file management

### Performance Considerations

- The export bypasses pagination to get all matching records
- Large datasets may take longer to process
- Consider implementing background job processing for very large exports

### Security

- All exports are user-specific (authenticated users only)
- Cache keys include user ID to prevent cross-user access
- Filter parameters are validated and sanitized

## Usage Examples

### 1. Export Patients with Submit Status = Yes

```bash
curl -X POST \
  http://your-domain.com/api/exportFilteredPatients \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "9901": "Yes"
  }'
```

### 2. Export Patients from Specific Hospital

```bash
curl -X POST \
  http://your-domain.com/api/exportFilteredPatients \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "2": "General Hospital"
  }'
```

### 3. Export with Multiple Filters

```bash
curl -X POST \
  http://your-domain.com/api/exportFilteredPatients \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "1": "John",
    "2": "General Hospital", 
    "9901": "Yes",
    "9902": "No"
  }'
```

## Error Handling

The API includes comprehensive error handling and logging:

- All operations are logged with relevant context
- Detailed error messages for debugging
- Graceful fallbacks for missing data
- Validation of filter parameters

## Integration Notes

This endpoint integrates seamlessly with the existing patient filtering system:

- Uses the same `PatientFilterService` for consistent filtering logic
- Shares filter conditions with the `patientFilters` endpoints
- Maintains the same authentication and authorization patterns
- Compatible with existing frontend implementations

## Support

For technical support or questions about this API, please refer to the application logs or contact the development team.
