# 🌍 EGYAKIN Backend Localization Implementation Guide

## 📋 **Overview**

The EGYAKIN backend now supports **English (en)** and **Arabic (ar)** localization based on the `Accept-Language` header from your frontend app. The system automatically detects the user's preferred language and returns appropriate responses.

## 🎯 **How It Works**

### **1. Language Detection Priority**
The system checks for language preference in this order:
1. **URL Parameter**: `?lang=ar` or `?lang=en`
2. **User Profile**: Saved locale in user's profile (if authenticated)
3. **Accept-Language Header**: `"en-US,en;q=0.9"` from your frontend
4. **Default**: Falls back to English (`en`)

### **2. Your Frontend Header**
Based on your provided header:
```json
{
  "accept-language": "en-US,en;q=0.9"
}
```

The system will:
- Parse `"en-US,en;q=0.9"`
- Extract `"en"` as the primary language
- Set the application locale to `en`
- Return all responses in English

## 🛠️ **Implementation Details**

### **Files Created/Modified:**

#### **1. Middleware: `app/Http/Middleware/SetLocale.php`**
- Automatically parses `Accept-Language` header
- Sets Laravel's locale for each request
- Logs locale detection for debugging

#### **2. Language Files:**
- `resources/lang/en/api.php` - English API messages
- `resources/lang/ar/api.php` - Arabic API messages  
- `resources/lang/en/validation.php` - English validation messages
- `resources/lang/ar/validation.php` - Arabic validation messages

#### **3. HTTP Kernel: `app/Http/Kernel.php`**
- Added `SetLocale` middleware to API routes
- Registered middleware alias `'locale'`

#### **4. Configuration: `config/app.php`**
- Added `'supported_locales' => ['en', 'ar']`

## 🧪 **Testing the Implementation**

### **Test Routes Available:**
```
GET  /api/v1/localization/test
POST /api/v1/localization/login
POST /api/v1/localization/patient
POST /api/v1/localization/points
```

### **Example 1: English Response**
```bash
curl -X GET "https://test.egyakin.com/api/v1/localization/test" \
  -H "Accept-Language: en-US,en;q=0.9" \
  -H "Content-Type: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "current_locale": "en",
  "accept_language_header": "en-US,en;q=0.9",
  "messages": {
    "login_success": "Login successful",
    "user_created": "User created successfully",
    "patient_created": "Patient created successfully",
    "milestone_reached": "Congratulations! You have reached 50 points",
    "validation_failed": "Validation failed",
    "unauthorized": "Unauthorized access"
  }
}
```

### **Example 2: Arabic Response**
```bash
curl -X GET "https://test.egyakin.com/api/v1/localization/test" \
  -H "Accept-Language: ar-SA,ar;q=0.9" \
  -H "Content-Type: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "current_locale": "ar",
  "accept_language_header": "ar-SA,ar;q=0.9",
  "messages": {
    "login_success": "تم تسجيل الدخول بنجاح",
    "user_created": "تم إنشاء المستخدم بنجاح",
    "patient_created": "تم إنشاء المريض بنجاح",
    "milestone_reached": "مبروك! لقد وصلت إلى 50 نقطة",
    "validation_failed": "فشل في التحقق من البيانات",
    "unauthorized": "غير مصرح بالوصول"
  }
}
```

### **Example 3: Test Login with Validation**
```bash
curl -X POST "https://test.egyakin.com/api/v1/localization/login" \
  -H "Accept-Language: ar-SA,ar;q=0.9" \
  -H "Content-Type: application/json" \
  -d '{}'
```

**Expected Arabic Response:**
```json
{
  "success": false,
  "message": "فشل في التحقق من البيانات",
  "errors": {
    "email": "حقل عنوان البريد الإلكتروني مطلوب",
    "password": "حقل كلمة المرور مطلوب"
  }
}
```

## 🔧 **How to Update Your Existing Controllers**

### **Before (Hardcoded English):**
```php
return response()->json([
    'message' => 'User created successfully',
    'data' => $user
]);
```

### **After (Localized):**
```php
return response()->json([
    'message' => __('api.user_created'),
    'data' => $user
]);
```

### **With Parameters:**
```php
return response()->json([
    'message' => __('api.milestone_reached', ['points' => $totalPoints])
]);
```

### **Validation Errors:**
```php
return response()->json([
    'message' => __('api.validation_failed'),
    'errors' => [
        'email' => __('validation.required', ['attribute' => __('validation.attributes.email')])
    ]
], 422);
```

## 📱 **Frontend Integration**

### **React Native / Mobile App:**
Your app should automatically send the correct `Accept-Language` header based on user's device language:

```javascript
// React Native example
const userLocale = Localization.locale; // 'en-US' or 'ar-SA'

fetch('https://test.egyakin.com/api/v1/login', {
  method: 'POST',
  headers: {
    'Accept-Language': userLocale,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ email, password })
});
```

### **Manual Language Override:**
```javascript
// Force Arabic
fetch('https://test.egyakin.com/api/v1/login?lang=ar', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ email, password })
});
```

## 🎯 **Available Translation Keys**

### **API Messages (`api.php`):**
- `login_success`, `login_failed`, `logout_success`
- `registration_success`, `registration_failed`
- `user_created`, `user_updated`, `user_deleted`, `user_not_found`
- `patient_created`, `patient_updated`, `patient_deleted`, `patient_not_found`
- `consultation_created`, `assessment_completed`, `outcome_recorded`
- `points_awarded`, `milestone_reached`, `score_updated`
- `notification_sent`, `email_sent`, `reminder_sent`
- `success`, `error`, `validation_failed`, `unauthorized`, `forbidden`
- And many more...

### **Validation Messages (`validation.php`):**
- Standard Laravel validation rules in both languages
- Custom attribute names for medical fields
- Localized error messages

## 🔍 **Debugging**

### **Check Current Locale:**
```php
$currentLocale = App::getLocale(); // 'en' or 'ar'
```

### **Log Files:**
The middleware logs locale detection in `storage/logs/laravel.log`:
```
[2025-09-19 15:30:45] local.INFO: Locale set for request {"detected_locale":"ar","accept_language":"ar-SA,ar;q=0.9","url_lang":null,"user_id":null,"endpoint":"\/api\/v1\/localization\/test"}
```

## 🚀 **Next Steps**

1. **Update Existing Controllers**: Replace hardcoded strings with `__('api.key')` calls
2. **Add More Translations**: Extend `resources/lang/*/api.php` with project-specific messages
3. **User Preference**: Add `locale` column to users table for persistent language preference
4. **Email Templates**: Localize notification emails based on user's preferred language
5. **Remove Test Routes**: Delete the `/localization/*` test routes in production

## 📝 **Example Implementation in Your Controllers**

```php
// app/Modules/Auth/Controllers/AuthController.php
public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => __('api.validation_failed'),
            'errors' => $validator->errors()
        ], 422);
    }

    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json([
            'success' => false,
            'message' => __('api.login_failed')
        ], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => __('api.login_success'),
        'data' => [
            'user' => $user,
            'token' => $token
        ]
    ]);
}
```

---

**🎉 Your EGYAKIN backend now automatically responds in the user's preferred language!**
