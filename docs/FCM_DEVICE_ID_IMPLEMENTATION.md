# 📱 FCM Device ID Implementation Guide

## Overview

This document explains the implementation of device ID support for FCM (Firebase Cloud Messaging) tokens in the EgyAkin application. This enhancement allows better management of push notifications across multiple devices per user.

## 🔧 **What Changed**

### Database Schema
- **Added new columns to `fcm_tokens` table:**
  - `device_id` (string, nullable, indexed)
  - `device_type` (string, nullable) - ios, android, web
  - `app_version` (string, nullable)
- **Updated unique constraints:**
  - Removed unique constraint on `token` only
  - Added composite unique constraint on `doctor_id + device_id`

### API Changes
**Login/Register endpoints now accept:**
```json
{
  "email": "user@example.com",
  "password": "password",
  "fcmToken": "eMyDb4q0HE2XsBJp1Z5t4c:APA91bG...",
  "deviceId": "63B6779A-3BFF-40B5-A084-F8371EBFE952",
  "deviceType": "ios",
  "appVersion": "1.0.0"
}
```

**FCM Token Storage endpoint (`/api/storeFCM`) now accepts:**
```json
{
  "token": "eMyDb4q0HE2XsBJp1Z5t4c:APA91bG...",
  "deviceId": "63B6779A-3BFF-40B5-A084-F8371EBFE952",
  "deviceType": "ios",
  "appVersion": "1.0.0"
}
```

## 🚀 **Key Features**

### 1. **Multi-Device Support**
- Users can now have multiple FCM tokens (one per device)
- Each device is uniquely identified by `deviceId`
- Token limit increased from 5 to 10 per user

### 2. **Smart Token Management**
- **Device-based uniqueness:** Same `deviceId` for a user will update the existing token instead of creating duplicates
- **Automatic cleanup:** Old tokens are automatically removed when limits are exceeded
- **Invalid token detection:** Firebase errors are parsed to remove invalid tokens

### 3. **Enhanced Validation**
- **FCM Token:** Minimum 152 characters, alphanumeric with colons, underscores, hyphens
- **Device ID:** 10-50 characters, alphanumeric with underscores and hyphens
- **Device Type:** Must be one of: `ios`, `android`, `web`
- **App Version:** Numeric version format (e.g., "1.0.0")

### 4. **Backward Compatibility**
- All existing functionality works without `deviceId`
- If no `deviceId` provided, falls back to token-based uniqueness
- Existing tokens without `device_id` continue to work

## 📋 **Database Migration**

Run the migration to add the new columns:

```bash
php artisan migrate
```

**Migration file:** `2024_12_20_000000_add_device_id_to_fcm_tokens_table.php`

## 🔍 **How It Works**

### Token Storage Logic
```php
// If deviceId is provided
$uniqueFields = ['doctor_id' => $userId, 'device_id' => $deviceId];

// If no deviceId (backward compatibility)
$uniqueFields = ['token' => $token];

FcmToken::updateOrCreate($uniqueFields, $tokenData);
```

### Device ID Examples
- **iOS:** `63B6779A-3BFF-40B5-A084-F8371EBFE952` (UUID format)
- **Android:** `android_device_12345` or similar
- **Web:** `web_browser_session_67890`

## 🧪 **Testing**

### Updated Test Scripts
1. **PHPUnit Tests:** Updated to include `deviceId` in test payloads
2. **Console Command:** Enhanced to test device-specific scenarios
3. **Postman Collection:** Added `device_id` variable and updated requests

### Test Scenarios
```bash
# Test device-specific token storage
php artisan test:push-notifications --validate

# Test with specific device
php artisan test --filter=it_updates_existing_device_token_instead_of_duplicating
```

## 📱 **Mobile App Integration**

### iOS Implementation
```swift
// Get device identifier
let deviceId = UIDevice.current.identifierForVendor?.uuidString ?? "unknown"

// Include in login/register
let loginData = [
    "email": email,
    "password": password,
    "fcmToken": fcmToken,
    "deviceId": deviceId,
    "deviceType": "ios",
    "appVersion": Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String ?? "1.0.0"
]
```

### Android Implementation
```kotlin
// Get device identifier
val deviceId = Settings.Secure.getString(contentResolver, Settings.Secure.ANDROID_ID)

// Include in login/register
val loginData = mapOf(
    "email" to email,
    "password" to password,
    "fcmToken" to fcmToken,
    "deviceId" to deviceId,
    "deviceType" to "android",
    "appVersion" to BuildConfig.VERSION_NAME
)
```

### Web Implementation
```javascript
// Generate or retrieve stored device ID
let deviceId = localStorage.getItem('device_id') || generateUUID();
localStorage.setItem('device_id', deviceId);

// Include in login/register
const loginData = {
    email: email,
    password: password,
    fcmToken: fcmToken,
    deviceId: deviceId,
    deviceType: 'web',
    appVersion: '1.0.0'
};
```

## 🔧 **New Service Methods**

### FcmTokenService
```php
// Store token with device info
$fcmTokenService->storeFcmToken($token, $deviceId, $deviceType, $appVersion);

// Get tokens for specific device
$tokens = $fcmTokenService->getTokensForDevice($deviceId);

// Get user's devices
$devices = $fcmTokenService->getUserDevices($userId);

// Remove token by device ID
$result = $fcmTokenService->removeFcmToken(null, $deviceId);
```

### Model Scopes
```php
// Get tokens for specific device
FcmToken::forDevice($deviceId)->get();

// Get tokens for specific user
FcmToken::forUser($userId)->get();

// Get tokens by device type
FcmToken::byDeviceType('ios')->get();
```

## 🎯 **Benefits**

1. **Better User Experience**
   - Notifications work correctly across all user devices
   - No duplicate notifications to the same device
   - Proper token management when users switch devices

2. **Improved Analytics**
   - Track which device types are most active
   - Monitor app version distribution
   - Better understanding of user behavior

3. **Enhanced Maintenance**
   - Easier to debug notification issues
   - Better cleanup of old/invalid tokens
   - Device-specific notification targeting

4. **Future-Proof**
   - Ready for device-specific notification features
   - Supports selective notification sending
   - Enables device-based user preferences

## 🚨 **Important Notes**

1. **Privacy:** Device IDs should be generated locally and not contain personal information
2. **Consistency:** Use the same device ID format across app versions
3. **Storage:** Device IDs should persist across app updates but regenerate on fresh installs
4. **Validation:** Always validate device ID format on the backend

## 📊 **Monitoring**

### Key Metrics to Track
- Number of devices per user
- Token refresh frequency by device type
- Failed notification delivery by device
- Device type distribution

### Logs to Monitor
```bash
# FCM token operations
tail -f storage/logs/laravel.log | grep -i "fcm.*device"

# Invalid token cleanup
tail -f storage/logs/laravel.log | grep -i "invalid.*token"
```

---

## 🎉 **Ready to Use!**

Your FCM system now supports device ID management! The implementation is backward compatible and includes comprehensive testing tools.

**Next Steps:**
1. Run the database migration
2. Update your mobile/web apps to send device IDs
3. Test with the provided test scripts
4. Monitor logs for any issues

For questions or issues, refer to the test scripts in `/scripts/` and the updated Postman collection.
