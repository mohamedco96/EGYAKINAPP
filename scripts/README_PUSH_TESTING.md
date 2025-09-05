# 🧪 Push Notification Testing Guide

This directory contains comprehensive testing tools for your push notification system.

## 📋 Available Test Scripts

### 1. **Laravel Console Command** (Recommended)
```bash
# Interactive menu
php artisan test:push-notifications

# Test specific user
php artisan test:push-notifications --user-id=123

# Test specific FCM token
php artisan test:push-notifications --token=your-fcm-token-here

# Send to all users (be careful!)
php artisan test:push-notifications --all

# Send to admin users only
php artisan test:push-notifications --admins

# Validate tokens without sending
php artisan test:push-notifications --validate
```

### 2. **PHPUnit Feature Tests**
```bash
# Run all push notification tests
php artisan test --filter=PushNotificationTest

# Run specific test
php artisan test --filter=it_can_store_fcm_token_via_api
```

### 3. **Manual PHP Script**
```bash
# Run manual test script
php scripts/test_push_notifications.php
```

### 4. **Postman Collection**
Import `scripts/postman_collection.json` into Postman for API testing.

## 🔧 Setup Instructions

### 1. **Prepare Test Environment**
```bash
# Make sure your Laravel app is running
php artisan serve

# Run migrations if needed
php artisan migrate

# Create test users with roles
php artisan db:seed --class=RoleSeeder
```

### 2. **Configure Test Variables**

#### For Laravel Commands:
No setup needed - uses your existing database.

#### For Manual PHP Script:
Edit `scripts/test_push_notifications.php`:
```php
$baseUrl = 'http://your-app-url.com'; // Change this
```

#### For Postman:
1. Import the collection
2. Set environment variables:
   - `base_url`: Your app URL
   - `auth_token`: Valid user token
   - `test_fcm_token`: Valid FCM token

### 3. **Get Authentication Token**
```bash
# Login via API to get token
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

## 🎯 Test Scenarios Covered

### ✅ **Token Management**
- Store valid FCM tokens
- Reject invalid token formats
- Handle duplicate tokens
- Limit tokens per user (5 max)
- Clean up old tokens

### ✅ **Notification Sending**
- Send to specific users
- Send to all users
- Send to admin users only
- Send different notification types
- Handle empty token arrays
- Validate notification data

### ✅ **Error Handling**
- Invalid token formats
- Missing notification data
- Firebase service errors
- Authentication failures
- Network connectivity issues

## 📊 Test Results Interpretation

### **Success Indicators:**
- ✅ `FCM token stored successfully`
- ✅ `Notification sent successfully`
- ✅ `Sent to X token(s)`

### **Expected Warnings:**
- ⚠️ `No FCM tokens found` (if no users have tokens)
- ⚠️ `Invalid FCM token format detected`
- ⚠️ `Authentication required`

### **Error Indicators:**
- ❌ `Failed to send push notification`
- ❌ `Invalid FCM token format`
- ❌ `User not found`

## 🔍 Debugging Tips

### **Check Logs**
```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# Filter for notification logs
tail -f storage/logs/laravel.log | grep -i "fcm\|notification\|push"
```

### **Verify Database**
```sql
-- Check FCM tokens
SELECT doctor_id, COUNT(*) as token_count, MAX(updated_at) as last_updated 
FROM fcm_tokens 
GROUP BY doctor_id;

-- Check recent notifications
SELECT * FROM notifications 
WHERE created_at > NOW() - INTERVAL 1 HOUR 
ORDER BY created_at DESC;
```

### **Firebase Console**
1. Go to [Firebase Console](https://console.firebase.google.com)
2. Select your project
3. Go to Cloud Messaging
4. Check for delivery reports

## 🛠 Maintenance Commands

### **Clean Up Old Tokens**
```bash
php artisan fcm:cleanup
```

### **Run Scheduled Cleanup** (Add to cron)
```bash
# Add to Laravel scheduler in app/Console/Kernel.php
$schedule->command('fcm:cleanup')->weekly();
```

## 📱 Mobile App Integration

### **When to Store FCM Tokens:**
```javascript
// 1. When Firebase token is received
messaging.onTokenRefresh((fcmToken) => {
    sendTokenToServer(fcmToken);
});

// 2. When app starts
const fcmToken = await messaging.getToken();
sendTokenToServer(fcmToken);

// 3. After successful login
loginUser().then(() => {
    sendTokenToServer(currentFcmToken);
});

function sendTokenToServer(token) {
    fetch('/api/storeFCM', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + authToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ token: token })
    });
}
```

## 🚨 Production Considerations

### **Before Going Live:**
1. ✅ Test with real FCM tokens from mobile devices
2. ✅ Verify Firebase project configuration
3. ✅ Test notification delivery to actual devices
4. ✅ Set up monitoring and logging
5. ✅ Configure rate limiting
6. ✅ Schedule token cleanup

### **Monitoring:**
- Track notification delivery rates
- Monitor Firebase quota usage
- Watch for invalid token patterns
- Set up alerts for failures

---

## 🎉 Quick Start

1. **Run the interactive test:**
   ```bash
   php artisan test:push-notifications
   ```

2. **Choose option 1** to validate tokens

3. **Choose option 4** to test admin notifications

4. **Import Postman collection** for API testing

Happy testing! 🚀
