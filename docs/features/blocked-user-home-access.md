# Blocked User Home Access Feature

## Overview
Implemented a feature that allows blocked users to access only the home endpoint (`/api/v1/homeNew`) while restricting access to all other API endpoints. This provides a limited access mode for blocked users instead of completely blocking them.

## Implementation

### 1. New Middleware: CheckBlockedUserWithHomeAccess
**File**: `app/Http/Middleware/CheckBlockedUserWithHomeAccess.php`

This middleware replaces the original `CheckBlockedUser` middleware and provides selective access for blocked users.

#### Key Features:
- **Selective Access**: Blocked users can only access `/api/v1/homeNew` endpoint
- **Localization Support**: Respects user's language preference for error messages
- **Comprehensive Logging**: Logs both allowed and denied access attempts
- **Clear Error Messages**: Provides specific error messages with allowed endpoint information

#### Logic Flow:
```php
if ($user->blocked) {
    // Set user's locale
    if ($user->locale && in_array($user->locale, ['en', 'ar'])) {
        \App::setLocale($user->locale);
    }

    // Check if accessing homeNew endpoint
    $isHomeNewEndpoint = (
        $routeName === 'homeNew' ||
        str_ends_with($routeUri, '/homeNew') ||
        str_contains($routeUri, '/api/v1/homeNew')
    );

    if (!$isHomeNewEndpoint) {
        // Block access and return error
        return response()->json([
            'value' => false,
            'message' => __('api.account_blocked'),
            'allowed_endpoint' => '/api/v1/homeNew',
        ], 403);
    }

    // Allow access and log it
}
```

### 2. Middleware Registration
**File**: `app/Http/Kernel.php`

Added the new middleware to the middleware aliases:
```php
'check.blocked.home' => \App\Http\Middleware\CheckBlockedUserWithHomeAccess::class,
```

### 3. Route Configuration Updates
**Files**: 
- `routes/api.php`
- `routes/api/v1.php`

Replaced the original `check.blocked` middleware with `check.blocked.home`:

**Before:**
```php
Route::group(['middleware' => ['auth:sanctum', 'check.blocked']], function () {
```

**After:**
```php
Route::group(['middleware' => ['auth:sanctum', 'check.blocked.home']], function () {
```

## API Behavior

### For Non-Blocked Users
- **Access**: Full access to all authenticated endpoints
- **Behavior**: No change from previous implementation

### For Blocked Users
- **Allowed Endpoint**: Only `/api/v1/homeNew`
- **Restricted Endpoints**: All other authenticated endpoints return 403 error

#### Successful Access (homeNew endpoint)
```json
{
  "value": true,
  "data": {
    // Home page data
  }
}
```

#### Blocked Access (other endpoints)
```json
{
  "value": false,
  "message": "Your account has been blocked. Please contact support.",
  "allowed_endpoint": "/api/v1/homeNew"
}
```

## Logging

### Successful Access Log
When a blocked user successfully accesses the home endpoint:
```php
\Log::info('Blocked user accessed allowed home endpoint', [
    'user_id' => $user->id,
    'email' => $user->email,
    'url' => $request->url(),
    'route_name' => $routeName,
    'route_uri' => $routeUri,
    'ip' => $request->ip(),
    'user_locale' => $user->locale,
]);
```

### Blocked Access Log
When a blocked user attempts to access restricted endpoints:
```php
\Log::warning('Blocked user attempted to access restricted endpoint', [
    'user_id' => $user->id,
    'email' => $user->email,
    'url' => $request->url(),
    'route_name' => $routeName,
    'route_uri' => $routeUri,
    'ip' => $request->ip(),
    'user_locale' => $user->locale,
]);
```

## Endpoint Detection

The middleware uses multiple methods to detect the homeNew endpoint:

1. **Route Name**: Checks if route name equals 'homeNew'
2. **URI Ending**: Checks if URI ends with '/homeNew'
3. **URI Contains**: Checks if URI contains '/api/v1/homeNew'

This ensures reliable detection regardless of how the route is accessed.

## Security Features

### 1. Comprehensive Blocking
- All endpoints except homeNew are blocked for blocked users
- No token revocation (unlike original middleware) to allow home access
- Clear error messages prevent confusion

### 2. Audit Trail
- All access attempts are logged with detailed information
- Separate log levels for allowed (info) vs denied (warning) access
- IP address tracking for security monitoring

### 3. Localization Support
- Error messages respect user's language preference
- Supports English and Arabic languages
- Locale is set before processing the request

## Use Cases

### 1. Gradual Account Suspension
Instead of completely blocking users, provide limited access to essential information.

### 2. Maintenance Mode for Specific Users
Allow certain users to access only basic functionality during maintenance.

### 3. Compliance Requirements
Meet regulatory requirements that may require providing access to basic account information even for suspended accounts.

## Testing

### Test Scenarios

#### 1. Non-Blocked User
```bash
# Should have full access
curl -H "Authorization: Bearer {token}" /api/v1/homeNew
curl -H "Authorization: Bearer {token}" /api/v1/patient/1
curl -H "Authorization: Bearer {token}" /api/v1/showSections/1
```

#### 2. Blocked User - Home Access
```bash
# Should succeed
curl -H "Authorization: Bearer {token}" /api/v1/homeNew
```

#### 3. Blocked User - Restricted Access
```bash
# Should return 403 with error message
curl -H "Authorization: Bearer {token}" /api/v1/patient/1
curl -H "Authorization: Bearer {token}" /api/v1/showSections/1
curl -H "Authorization: Bearer {token}" /api/v1/searchNew
```

### Expected Responses

#### Blocked User Accessing Restricted Endpoint
```json
{
  "value": false,
  "message": "Your account has been blocked. Please contact support.",
  "allowed_endpoint": "/api/v1/homeNew"
}
```

## Migration from Original Middleware

### Changes Made
1. **Middleware Replacement**: `check.blocked` → `check.blocked.home`
2. **Behavior Change**: Complete blocking → Selective blocking
3. **Token Handling**: No token revocation for blocked users
4. **Error Response**: Added allowed endpoint information

### Backward Compatibility
- API response structure remains the same for non-blocked users
- Error response enhanced with additional information
- No breaking changes for existing integrations

## Configuration

### Environment Variables
No additional environment variables required.

### Database Schema
Uses existing `users.blocked` boolean column.

### Localization Keys
Uses existing `api.account_blocked` translation key.

## Benefits

### 1. User Experience
- Blocked users can still access essential home information
- Clear guidance on what they can access
- Localized error messages

### 2. Administrative Control
- Granular control over user access
- Detailed logging for monitoring
- Flexible blocking strategy

### 3. Compliance
- Meets requirements for providing basic access
- Audit trail for regulatory compliance
- Transparent access control

This implementation provides a balanced approach to user blocking, maintaining security while allowing limited access to essential functionality.
