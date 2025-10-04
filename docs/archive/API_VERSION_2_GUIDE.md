# API Version 2 Implementation Guide

## 🎉 Overview

Version 2 of the EGYAKIN API is now active and ready for all new features and changes. This document provides a comprehensive guide for working with V2.

## 📋 Table of Contents

1. [What's New in V2](#whats-new-in-v2)
2. [Route Structure](#route-structure)
3. [Using V2 Endpoints](#using-v2-endpoints)
4. [Adding New Features to V2](#adding-new-features-to-v2)
5. [Customizing V2 Controllers](#customizing-v2-controllers)
6. [Migration Guide](#migration-guide)
7. [Best Practices](#best-practices)

---

## What's New in V2

### Current Status
- ✅ **All V2 routes are active** and ready to use
- ✅ **28 controllers** created in `app/Http/Controllers/Api/V2/`
- ✅ **Complete route file** at `routes/api/v2.php`
- ✅ **Backward compatible** - V1 and non-versioned routes still work
- ✅ **Delegation pattern** - V2 → V1 → Module Controllers

### Why V2?
- **Clean slate** for new features without affecting existing implementations
- **Better versioning** for mobile apps and API consumers
- **Easier maintenance** - changes to V2 don't break V1
- **Future-proof** architecture

---

## Route Structure

### Available API Versions

| Version | Prefix | Status | Use Case |
|---------|--------|--------|----------|
| **V2** | `/api/v2/` | ✅ Active | **All new development** |
| V1 | `/api/v1/` | ✅ Active | Existing features |
| Legacy | `/api/` | ✅ Active | Backward compatibility |

### Example Endpoints

```
# Version 2 (Use for new features)
POST   /api/v2/login
GET    /api/v2/users
POST   /api/v2/patient
GET    /api/v2/settings
POST   /api/v2/consultations
GET    /api/v2/feed/posts

# Version 1 (Still supported)
POST   /api/v1/login
GET    /api/v1/users

# Legacy (Backward compatible)
POST   /api/login
GET    /api/users
```

---

## Using V2 Endpoints

### Authentication

V2 uses the same authentication as V1 (Laravel Sanctum):

```javascript
// Login to get token
const response = await fetch('/api/v2/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        email: 'doctor@example.com',
        password: 'password123'
    })
});

const data = await response.json();
const token = data.token;

// Use token for authenticated requests
const userResponse = await fetch('/api/v2/users', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

### Response Format

V2 maintains the same response format as V1:

```json
{
    "value": true,
    "message": "Operation successful",
    "data": {
        // Response data here
    }
}
```

### Error Handling

```json
{
    "value": false,
    "message": "Error description",
    "errors": {
        // Validation errors if applicable
    }
}
```

---

## Adding New Features to V2

### Step 1: Determine if You Need a New Controller Method

For a new feature, you have two options:

#### Option A: Add to Existing Controller

If the feature fits within an existing controller's responsibility:

```php
// app/Http/Controllers/Api/V2/PatientsController.php

/**
 * NEW V2 Feature: Export patient analytics
 */
public function exportAnalytics(Request $request)
{
    // Implement your V2-specific logic here
    // This method doesn't exist in V1
    
    $analytics = // ... your logic
    
    return response()->json([
        'value' => true,
        'message' => 'Analytics exported successfully',
        'data' => $analytics
    ]);
}
```

Then add the route:

```php
// routes/api/v2.php

Route::post('/patient/analytics/export', [PatientsController::class, 'exportAnalytics']);
```

#### Option B: Create New Controller

For entirely new modules:

```php
// app/Http/Controllers/Api/V2/AnalyticsController.php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function dashboard(Request $request)
    {
        // Your implementation
    }
    
    public function export(Request $request)
    {
        // Your implementation
    }
}
```

---

## Customizing V2 Controllers

### Understanding the Delegation Chain

```
V2 Controller → V1 Controller → Module Controller
```

### Example: Customizing Login Response in V2

```php
// app/Http/Controllers/Api/V2/AuthController.php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\AuthController as V1AuthController;
use App\Modules\Auth\Requests\LoginRequest;

class AuthController extends Controller
{
    protected $authController;

    public function __construct(V1AuthController $authController)
    {
        $this->authController = $authController;
    }

    /**
     * V2 Custom Login - Enhanced with additional data
     */
    public function login(LoginRequest $request)
    {
        // Get V1 response
        $response = $this->authController->login($request);
        $data = $response->getData(true);
        
        // Add V2-specific enhancements
        if ($data['value'] ?? false) {
            $data['api_version'] = '2.0';
            $data['features'] = [
                'enhanced_notifications',
                'real_time_updates',
                'advanced_analytics'
            ];
        }
        
        return response()->json($data);
    }
    
    /**
     * Other methods still delegate to V1
     */
    public function register(RegisterRequest $request)
    {
        return $this->authController->register($request);
    }
    
    // ... rest of methods
}
```

### Example: Adding Entirely New Functionality

```php
// app/Http/Controllers/Api/V2/PatientsController.php

/**
 * V2 NEW: Bulk patient operations
 */
public function bulkUpdate(Request $request)
{
    $request->validate([
        'patient_ids' => 'required|array',
        'patient_ids.*' => 'exists:patients,id',
        'action' => 'required|in:archive,restore,delete'
    ]);
    
    // Your V2-specific implementation
    $patients = Patient::whereIn('id', $request->patient_ids)->get();
    
    foreach ($patients as $patient) {
        switch ($request->action) {
            case 'archive':
                $patient->update(['archived' => true]);
                break;
            case 'restore':
                $patient->update(['archived' => false]);
                break;
            case 'delete':
                $patient->delete();
                break;
        }
    }
    
    return response()->json([
        'value' => true,
        'message' => 'Bulk operation completed',
        'data' => [
            'affected_count' => count($patients),
            'action' => $request->action
        ]
    ]);
}
```

---

## Migration Guide

### For Mobile Apps

#### Gradual Migration Strategy

```javascript
// config.js
const API_VERSION = 'v2'; // Change this to migrate
const BASE_URL = `/api/${API_VERSION}`;

// All your API calls
const login = (credentials) => {
    return fetch(`${BASE_URL}/login`, {
        method: 'POST',
        body: JSON.stringify(credentials)
    });
};
```

#### Version Detection

```javascript
// Check which version to use based on app version
const getAPIVersion = () => {
    const appVersion = getAppVersion(); // Your method to get app version
    
    if (appVersion >= '2.0.0') {
        return 'v2';
    }
    return 'v1';
};
```

### For Web Frontend

```javascript
// api.js
const API_BASE = process.env.REACT_APP_API_VERSION === 'v2' 
    ? '/api/v2' 
    : '/api/v1';

export const api = {
    login: (credentials) => axios.post(`${API_BASE}/login`, credentials),
    getUsers: () => axios.get(`${API_BASE}/users`),
    // ... other endpoints
};
```

---

## Best Practices

### 1. Always Use V2 for New Features

```php
// ❌ Don't add new features to V1
// app/Http/Controllers/Api/V1/PatientsController.php
public function newFeature() { }

// ✅ Add new features to V2
// app/Http/Controllers/Api/V2/PatientsController.php
public function newFeature() { }
```

### 2. Version Your Response Changes

```php
// V2 - Enhanced response structure
return response()->json([
    'value' => true,
    'message' => 'Success',
    'data' => [
        'user' => $user,
        'metadata' => [  // V2 enhancement
            'api_version' => '2.0',
            'timestamp' => now(),
        ]
    ]
]);
```

### 3. Document V2-Specific Changes

```php
/**
 * V2: Enhanced patient creation with validation
 * 
 * New features in V2:
 * - Advanced validation rules
 * - Automatic notification to assigned doctors
 * - Real-time updates via websocket
 * 
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function storePatient(Request $request)
{
    // Implementation
}
```

### 4. Maintain Backward Compatibility in Database

```php
// ✅ Add new columns, don't modify existing ones
Schema::table('users', function (Blueprint $table) {
    $table->json('v2_preferences')->nullable(); // V2 specific
});

// ❌ Don't change existing column behavior
Schema::table('users', function (Blueprint $table) {
    $table->string('email')->change(); // This affects all versions
});
```

### 5. Test Both Versions

```php
// tests/Feature/Api/V2/AuthTest.php

public function test_v2_login_returns_enhanced_response()
{
    $response = $this->postJson('/api/v2/login', [
        'email' => 'test@example.com',
        'password' => 'password'
    ]);
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'value',
                 'message',
                 'data' => [
                     'token',
                     'user',
                     'api_version',  // V2 specific
                     'features'      // V2 specific
                 ]
             ]);
}
```

---

## Quick Reference

### File Structure

```
app/Http/Controllers/Api/
├── V1/
│   ├── AuthController.php
│   ├── PatientsController.php
│   └── ... (28 controllers)
└── V2/
    ├── AuthController.php        ← Delegates to V1
    ├── PatientsController.php    ← Delegates to V1
    └── ... (28 controllers)      ← All delegate to V1

routes/
├── api.php              ← Main file, includes both versions
└── api/
    ├── v1.php          ← V1 routes
    └── v2.php          ← V2 routes (NEW)
```

### Common Commands

```bash
# Check routes
php artisan route:list --path=v2

# Clear route cache
php artisan route:clear

# Test V2 endpoint
curl -X POST http://localhost:8000/api/v2/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password"}'
```

### Controller Template for New Features

```php
<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class YourNewController extends Controller
{
    /**
     * Your new V2 feature
     */
    public function yourMethod(Request $request)
    {
        // Validate
        $validated = $request->validate([
            // Your validation rules
        ]);
        
        // Process
        $result = // Your logic
        
        // Return
        return response()->json([
            'value' => true,
            'message' => 'Success message',
            'data' => $result
        ]);
    }
}
```

---

## Support & Questions

### Need Help?

- Check existing V1 implementation for reference
- Review module controllers in `app/Modules/`
- Test endpoints using Postman collection in `scripts/postman_collection.json`

### Adding New Routes

1. Add controller method in `app/Http/Controllers/Api/V2/YourController.php`
2. Add route in `routes/api/v2.php`
3. Test the endpoint
4. Document in this file

---

## Version History

| Version | Release Date | Status | Notes |
|---------|-------------|--------|-------|
| V2 | October 2025 | ✅ Active | All new features go here |
| V1 | - | ✅ Active | Existing features |
| Legacy | - | ✅ Active | Backward compatibility |

---

**Ready to build amazing features in V2! 🚀**

