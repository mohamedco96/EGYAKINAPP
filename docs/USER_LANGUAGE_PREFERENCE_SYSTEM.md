# 🌍 User Language Preference System

## 📋 **Overview**

The EGYAKIN backend now supports **persistent user language preferences**. When a user changes their language in the frontend app, it gets saved to their profile and will be used for all future API responses.

## 🎯 **How It Works**

### **Language Detection Priority (Updated)**
The system now checks for language preference in this order:
1. **URL Parameter**: `?lang=ar` or `?lang=en` (highest priority - temporary override)
2. **User's Saved Preference**: Stored in `users.locale` column (persistent preference)
3. **Accept-Language Header**: From frontend app (fallback)
4. **Default**: English (`en`) (final fallback)

## 🛠️ **New API Endpoints**

### **1. Update User Language Preference**
```
POST /api/v1/user/locale
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept-Language: en-US,en;q=0.9
```

**Request Body:**
```json
{
  "locale": "ar"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "تم تحديث الملف الشخصي بنجاح",
  "data": {
    "locale": "ar",
    "user": {
      "id": 1,
      "name": "Dr. Ahmed",
      "email": "ahmed@example.com",
      "locale": "ar"
    }
  }
}
```

**Validation Error (422):**
```json
{
  "success": false,
  "message": "فشل في التحقق من البيانات",
  "errors": {
    "locale": ["The selected locale is invalid."]
  }
}
```

### **2. Get User Language Preference**
```
GET /api/v1/user/locale
```

**Headers:**
```
Authorization: Bearer {token}
Accept-Language: en-US,en;q=0.9
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "current_locale": "ar",
    "user_preferred_locale": "ar",
    "supported_locales": ["en", "ar"],
    "locale_names": {
      "en": "English",
      "ar": "العربية"
    }
  }
}
```

### **3. Test Localized Response**
```
GET /api/v1/user/locale/test
```

**Headers:**
```
Authorization: Bearer {token}
Accept-Language: en-US,en;q=0.9
```

**Success Response (200):**
```json
{
  "success": true,
  "current_locale": "ar",
  "user_preferred_locale": "ar",
  "localized_messages": {
    "welcome": "تم تسجيل الدخول بنجاح",
    "user_created": "تم إنشاء المستخدم بنجاح",
    "patient_created": "تم إنشاء المريض بنجاح",
    "points_awarded": "تم منح النقاط بنجاح",
    "milestone_reached": "مبروك! لقد وصلت إلى 100 نقطة",
    "validation_failed": "فشل في التحقق من البيانات",
    "unauthorized": "غير مصرح بالوصول"
  },
  "validation_examples": {
    "required_email": "حقل عنوان البريد الإلكتروني مطلوب",
    "required_password": "حقل كلمة المرور مطلوب",
    "invalid_email": "يجب أن يكون عنوان البريد الإلكتروني عنوان بريد إلكتروني صالح"
  },
  "debug_info": {
    "accept_language_header": "en-US,en;q=0.9",
    "url_lang_param": null,
    "user_id": 1,
    "timestamp": "2025-09-20T02:30:45.000000Z"
  }
}
```

## 📱 **Frontend Integration**

### **React Native / Mobile App Example:**

#### **1. Language Selector Component**
```javascript
const LanguageSelector = () => {
  const [currentLocale, setCurrentLocale] = useState('en');
  
  const changeLanguage = async (newLocale) => {
    try {
      const response = await fetch('/api/v1/user/locale', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${userToken}`,
          'Content-Type': 'application/json',
          'Accept-Language': newLocale === 'ar' ? 'ar-SA,ar;q=0.9' : 'en-US,en;q=0.9'
        },
        body: JSON.stringify({ locale: newLocale })
      });
      
      const result = await response.json();
      
      if (result.success) {
        setCurrentLocale(newLocale);
        // Update app's locale state
        I18n.locale = newLocale;
        // Show success message in new language
        Alert.alert('Success', result.message);
      }
    } catch (error) {
      console.error('Failed to update language:', error);
    }
  };

  return (
    <View>
      <TouchableOpacity onPress={() => changeLanguage('en')}>
        <Text>English</Text>
      </TouchableOpacity>
      <TouchableOpacity onPress={() => changeLanguage('ar')}>
        <Text>العربية</Text>
      </TouchableOpacity>
    </View>
  );
};
```

#### **2. Initialize User Language on App Start**
```javascript
const initializeUserLanguage = async () => {
  try {
    const response = await fetch('/api/v1/user/locale', {
      headers: {
        'Authorization': `Bearer ${userToken}`,
        'Accept-Language': Localization.locale
      }
    });
    
    const result = await response.json();
    
    if (result.success) {
      const userPreferredLocale = result.data.user_preferred_locale;
      I18n.locale = userPreferredLocale;
      setAppLanguage(userPreferredLocale);
    }
  } catch (error) {
    console.error('Failed to get user language:', error);
  }
};

// Call on app startup
useEffect(() => {
  if (userToken) {
    initializeUserLanguage();
  }
}, [userToken]);
```

#### **3. Automatic Header Setting**
```javascript
// Set up axios interceptor to always send correct Accept-Language
axios.interceptors.request.use((config) => {
  const currentLocale = I18n.locale || 'en';
  config.headers['Accept-Language'] = currentLocale === 'ar' 
    ? 'ar-SA,ar;q=0.9' 
    : 'en-US,en;q=0.9';
  return config;
});
```

## 🗄️ **Database Changes**

### **Migration Added:**
```sql
-- Add locale column to users table
ALTER TABLE users ADD COLUMN locale VARCHAR(2) DEFAULT 'en' AFTER email_verified_at;
ALTER TABLE users ADD INDEX locale_index (locale);
```

### **User Model Updated:**
```php
// Added to $fillable array
'locale'

// Added to $casts array
'locale' => 'string'
```

## 🔄 **Middleware Behavior**

The `SetLocale` middleware now prioritizes user's saved preference:

1. **First Request** (new user):
   - Uses `Accept-Language` header → Sets locale to `en`
   - User changes language via `/api/v1/user/locale` → Saves `ar` to database

2. **Subsequent Requests**:
   - Middleware finds `users.locale = 'ar'` → Sets locale to `ar`
   - All responses are now in Arabic
   - `Accept-Language` header is ignored (user preference takes priority)

3. **Temporary Override**:
   - URL parameter `?lang=en` → Temporarily uses English for that request only
   - User's saved preference remains unchanged

## 🧪 **Testing Scenarios**

### **Scenario 1: New User (No Saved Preference)**
```bash
# User's first API call
curl -H "Authorization: Bearer token123" \
     -H "Accept-Language: ar-SA,ar;q=0.9" \
     "https://test.egyakin.com/api/v1/user/locale/test"

# Response will be in Arabic (from Accept-Language header)
# User's locale in database is still 'en' (default)
```

### **Scenario 2: User Changes Language**
```bash
# User changes to Arabic
curl -X POST "https://test.egyakin.com/api/v1/user/locale" \
     -H "Authorization: Bearer token123" \
     -H "Content-Type: application/json" \
     -d '{"locale": "ar"}'

# Response: "تم تحديث الملف الشخصي بنجاح"
# User's locale in database is now 'ar'
```

### **Scenario 3: Subsequent Requests**
```bash
# Any future API call (even with English Accept-Language)
curl -H "Authorization: Bearer token123" \
     -H "Accept-Language: en-US,en;q=0.9" \
     "https://test.egyakin.com/api/v1/patients"

# Response will be in Arabic (user's saved preference)
# Accept-Language header is ignored
```

### **Scenario 4: Temporary Override**
```bash
# Force English for one request
curl -H "Authorization: Bearer token123" \
     "https://test.egyakin.com/api/v1/patients?lang=en"

# Response will be in English (URL parameter override)
# User's saved preference remains 'ar'
```

## 🔍 **Debugging & Logging**

### **Locale Detection Logs:**
```
[2025-09-20 02:30:45] local.INFO: Locale set for request {
  "detected_locale": "ar",
  "accept_language": "en-US,en;q=0.9",
  "url_lang": null,
  "user_id": 1,
  "user_saved_locale": "ar",
  "endpoint": "/api/v1/patients"
}
```

### **Language Change Logs:**
```
[2025-09-20 02:30:45] local.INFO: User locale updated {
  "user_id": 1,
  "old_locale": "en",
  "new_locale": "ar",
  "ip_address": "192.168.1.100",
  "user_agent": "MyApp/1.0"
}
```

## 🚀 **Migration Instructions**

### **1. Run Migration (Production)**
```bash
# On production server
php artisan migrate

# This adds the 'locale' column to users table
# Existing users will have locale = 'en' (default)
```

### **2. Update Frontend App**
1. Add language selector UI component
2. Implement `/api/v1/user/locale` POST call when user changes language
3. Call `/api/v1/user/locale` GET on app startup to get user's preference
4. Set up automatic `Accept-Language` header based on user's choice

### **3. Test the System**
```bash
# Test language change
curl -X POST "https://test.egyakin.com/api/v1/user/locale" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"locale": "ar"}'

# Test localized response
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "https://test.egyakin.com/api/v1/user/locale/test"
```

## 📝 **Summary**

✅ **User language preferences are now persistent**  
✅ **Frontend can change user's language via API**  
✅ **All future responses use saved preference**  
✅ **Backward compatible with Accept-Language header**  
✅ **Temporary overrides via URL parameter**  
✅ **Comprehensive logging and debugging**  

Your users can now change their language in the app, and it will be remembered for all future interactions! 🎉
