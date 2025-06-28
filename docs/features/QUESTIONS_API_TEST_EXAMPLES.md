# Questions API Test Examples

## Test the Fixed POST /api/questions Endpoint

### ✅ Valid Request Example:
```bash
curl -X POST "https://yourapp.com/api/questions" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "section_id": 1,
    "section_name": "Patient Information",
    "question": "What is your age?",
    "type": "number",
    "keyboard_type": "number",
    "mandatory": true,
    "sort": 1
  }'
```

### ✅ Valid Request with Values:
```bash
curl -X POST "https://yourapp.com/api/questions" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "section_id": 2,
    "section_name": "Medical History",
    "question": "Do you have any allergies?",
    "values": "[\"Yes\", \"No\", \"Unknown\"]",
    "type": "select",
    "mandatory": true,
    "sort": 2
  }'
```

### ❌ Invalid Request (Missing Required Fields):
```bash
curl -X POST "https://yourapp.com/api/questions" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "question": "Incomplete question"
  }'
```

**Expected Response**: Validation error with detailed messages

### ❌ Invalid Request (Invalid Section ID):
```bash
curl -X POST "https://yourapp.com/api/questions" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "section_id": 999,
    "section_name": "Invalid Section",
    "question": "Test question",
    "type": "text"
  }'
```

**Expected Response**: "The selected section does not exist" error

## Test Other Endpoints

### GET /api/questions
```bash
curl -X GET "https://yourapp.com/api/questions" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### GET /api/questions/{section_id}
```bash
curl -X GET "https://yourapp.com/api/questions/1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### PUT /api/questions/{id}
```bash
curl -X PUT "https://yourapp.com/api/questions/1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "question": "Updated question text",
    "sort": 5
  }'
```

### DELETE /api/questions/{id}
```bash
curl -X DELETE "https://yourapp.com/api/questions/1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Expected Response Format

### Success Response:
```json
{
  "value": true,
  "data": {
    "id": 1,
    "section_id": 1,
    "section_name": "Patient Information",
    "question": "What is your age?",
    "values": null,
    "type": "number",
    "keyboard_type": "number",
    "mandatory": true,
    "hidden": false,
    "skip": false,
    "sort": 1,
    "created_at": "2025-06-28T03:57:29.000000Z",
    "updated_at": "2025-06-28T03:57:29.000000Z"
  }
}
```

### Validation Error Response:
```json
{
  "value": false,
  "message": "Validation failed",
  "errors": {
    "section_id": ["The section ID is required."],
    "section_name": ["The section name is required."],
    "question": ["The question text is required."],
    "type": ["The question type is required."]
  }
}
```

### General Error Response:
```json
{
  "value": false,
  "message": "Failed to create question: [error details]"
}
```

---

**Note**: Replace `YOUR_TOKEN` with a valid authentication token and update the base URL to match your environment.
