# API V2 Quick Start Guide

## 🚀 Quick Reference

### Base URL
```
Production: https://your-domain.com/api/v2
Development: http://localhost:8000/api/v2
```

---

## 📌 Essential Information

### ✅ V2 is Now Active
- All endpoints are ready
- 28 controllers created
- All routes configured
- **Use V2 for all new features**

### 🔑 Authentication
Same as V1 - uses Laravel Sanctum Bearer tokens

---

## 📋 Most Common Endpoints

### Authentication
```
POST   /api/v2/register
POST   /api/v2/login
POST   /api/v2/logout
POST   /api/v2/forgotpassword
POST   /api/v2/resetpassword
```

### Users
```
GET    /api/v2/users
GET    /api/v2/users/{id}
PUT    /api/v2/users
PUT    /api/v2/users/{id}
DELETE /api/v2/users/{id}
POST   /api/v2/changePassword
POST   /api/v2/upload-profile-image
```

### Patients
```
POST   /api/v2/patient
GET    /api/v2/patient/{section_id}/{patient_id}
PUT    /api/v2/patientsection/{section_id}/{patient_id}
DELETE /api/v2/patient/{id}
POST   /api/v2/searchNew
GET    /api/v2/homeNew
GET    /api/v2/currentPatientsNew
GET    /api/v2/allPatientsNew
POST   /api/v2/uploadFile
GET    /api/v2/patientFilters
POST   /api/v2/patientFilters
POST   /api/v2/exportFilteredPatients
```

### Sections & Questions
```
GET    /api/v2/showSections/{patient_id}
GET    /api/v2/questions
GET    /api/v2/questions/{section_id}
GET    /api/v2/questions/{section_id}/{patient_id}
POST   /api/v2/questions
PUT    /api/v2/questions/{id}
```

### Feed & Posts
```
GET    /api/v2/feed/posts
POST   /api/v2/feed/posts
PUT    /api/v2/feed/posts/{id}
DELETE /api/v2/feed/posts/{id}
POST   /api/v2/feed/posts/{id}/likeOrUnlikePost
POST   /api/v2/feed/posts/{id}/saveOrUnsavePost
POST   /api/v2/feed/posts/{id}/comment
GET    /api/v2/feed/trendingPosts
POST   /api/v2/feed/searchPosts
POST   /api/v2/feed/searchHashtags
```

### Groups
```
GET    /api/v2/groups
GET    /api/v2/mygroups
POST   /api/v2/groups
GET    /api/v2/groups/{id}
PUT    /api/v2/groups/{id}
DELETE /api/v2/groups/{id}
POST   /api/v2/groups/{groupId}/join
POST   /api/v2/groups/{groupId}/leave
POST   /api/v2/groups/{groupId}/invite
GET    /api/v2/groups/{groupId}/members
```

### Consultations
```
POST   /api/v2/consultations
GET    /api/v2/consultations/sent
GET    /api/v2/consultations/received
GET    /api/v2/consultations/{id}
PUT    /api/v2/consultations/{id}
POST   /api/v2/consultations/{id}/add-doctors
POST   /api/v2/consultations/{id}/replies
DELETE /api/v2/consultations/{consultationId}/doctors/{doctorId}
```

### Notifications
```
GET    /api/v2/notification
GET    /api/v2/shownotification
POST   /api/v2/notification
PUT    /api/v2/notification/{id}
DELETE /api/v2/notification/{id}
PUT    /api/v2/notification (mark all as read)
POST   /api/v2/storeFCM
```

### Localized Notifications (V2 Feature)
```
GET    /api/v2/notifications/localized
GET    /api/v2/notifications/localized/new
POST   /api/v2/notifications/localized/{id}/read
POST   /api/v2/notifications/localized/read-all
```

### AI Consultation
```
POST   /api/v2/AIconsultation/{patientId}
GET    /api/v2/AIconsultation-history/{patientId}
```

### Achievements
```
GET    /api/v2/achievements
POST   /api/v2/achievements
GET    /api/v2/achievement/{id}
GET    /api/v2/users/{user}/achievements
```

### Settings
```
GET    /api/v2/settings
POST   /api/v2/settings
GET    /api/v2/settings/{settings}
PUT    /api/v2/settings/{settings}
```

### Locale (V2 Feature)
```
POST   /api/v2/user/locale
GET    /api/v2/user/locale
```

---

## 🔨 Quick Code Examples

### JavaScript/React Example
```javascript
const API_BASE = '/api/v2';

// Login
const login = async (email, password) => {
    const response = await fetch(`${API_BASE}/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    });
    const data = await response.json();
    return data.token;
};

// Authenticated Request
const getUsers = async (token) => {
    const response = await fetch(`${API_BASE}/users`, {
        headers: { 
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    });
    return await response.json();
};
```

### PHP/Laravel Example
```php
use Illuminate\Support\Facades\Http;

// Make request to V2 API
$response = Http::withToken($token)
    ->get('https://your-domain.com/api/v2/users');

if ($response->successful()) {
    $users = $response->json()['data'];
}
```

### cURL Example
```bash
# Login
curl -X POST http://localhost:8000/api/v2/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Get users (with token)
curl http://localhost:8000/api/v2/users \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 📊 Response Format

### Success Response
```json
{
    "value": true,
    "message": "Success message",
    "data": {
        // Your data here
    }
}
```

### Error Response
```json
{
    "value": false,
    "message": "Error message",
    "errors": {
        "field": ["Error details"]
    }
}
```

---

## 🛠️ Adding New Features to V2

### Step 1: Add Controller Method
```php
// app/Http/Controllers/Api/V2/YourController.php

public function yourNewFeature(Request $request)
{
    // Your logic here
    
    return response()->json([
        'value' => true,
        'message' => 'Success',
        'data' => $result
    ]);
}
```

### Step 2: Add Route
```php
// routes/api/v2.php

Route::post('/your-endpoint', [YourController::class, 'yourNewFeature']);
```

### Step 3: Test
```bash
php artisan route:list --path=v2
```

---

## ⚡ Key Differences from V1

### V2 Enhancements
- ✨ Better localization support
- 🔔 Enhanced notification system
- 📊 Improved analytics endpoints (coming soon)
- 🎯 More granular permissions (coming soon)
- 🚀 Better performance optimizations

### Maintained Features
- ✅ Same authentication mechanism
- ✅ Same response format
- ✅ All existing functionality
- ✅ Backward compatible

---

## 📚 Full Documentation

- **Complete Guide**: See `docs/api/API_VERSION_2_GUIDE.md`
- **V1 Reference**: See `docs/api/API_VERSIONING_IMPLEMENTATION.md`
- **Migration Guide**: See V2 Guide Section 6

---

## 🎯 Remember

1. **Use V2 for all new features**
2. V2 routes start with `/api/v2/`
3. Authentication works the same as V1
4. Response format is consistent
5. All 28 controllers are ready to use

---

**V2 is ready! Start building! 🚀**

---

## 📞 Quick Commands

```bash
# List all V2 routes
php artisan route:list --path=v2

# Clear cache
php artisan route:clear
php artisan config:clear
php artisan cache:clear

# Test endpoint
php artisan tinker
>>> \Illuminate\Support\Facades\Route::has('v2.login')
```

---

## 🔍 Troubleshooting

### Routes not found?
```bash
php artisan route:clear
php artisan config:clear
```

### Controllers not found?
```bash
composer dump-autoload
```

### Need to verify V2 is active?
```bash
php artisan route:list --path=v2 | head -20
```

---

**Happy Coding with V2! 🎉**

