# Database Notification Localization System

## 🌍 **Overview**

The EGYAKIN backend now supports **complete localization for database notifications**. Instead of storing hardcoded English text in the `notifications` table, the system now stores localization keys and parameters, allowing notifications to be displayed in the user's preferred language when retrieved.

## 🏗️ **System Architecture**

### **Database Schema Changes**

The `notifications` table has been enhanced with localization fields:

```sql
ALTER TABLE notifications ADD COLUMN localization_key VARCHAR(255) NULL;
ALTER TABLE notifications ADD COLUMN localization_params JSON NULL;
ALTER TABLE notifications ADD INDEX idx_localization_key (localization_key);
```

### **New Fields:**
- `localization_key`: The translation key (e.g., `'api.notification_post_liked'`)
- `localization_params`: JSON object containing parameters for the translation (e.g., `{"name": "Dr. Ahmed"}`)

## 📋 **AppNotification Model Enhancements**

### **New Methods:**

```php
// Get localized content for specific locale
$notification->getLocalizedContent('ar'); // Arabic
$notification->getLocalizedContent('en'); // English

// Create localized notification
AppNotification::createLocalized([
    'doctor_id' => $doctorId,
    'type' => 'PostLike',
    'localization_key' => 'api.notification_post_liked',
    'localization_params' => ['name' => 'Dr. Ahmed'],
]);

// Accessor for current locale
$notification->localized_content; // Uses current app locale
```

### **Backward Compatibility:**
- Existing notifications with `content` field still work
- New notifications store both localization data AND fallback English content
- System gracefully handles mixed old/new notification formats

## 🔄 **Notification Creation Updates**

### **Before (Hardcoded):**
```php
AppNotification::create([
    'doctor_id' => $doctorId,
    'type' => 'PostLike',
    'content' => 'Dr. Ahmed liked your post', // ❌ Hardcoded English
]);
```

### **After (Localized):**
```php
AppNotification::createLocalized([
    'doctor_id' => $doctorId,
    'type' => 'PostLike',
    'localization_key' => 'api.notification_post_liked',
    'localization_params' => ['name' => 'Dr. Ahmed'],
    // ✅ Automatically generates fallback English content
]);
```

## 🗣️ **Supported Notification Types**

### **1. Social Interactions**
- **Post Likes**: `api.notification_post_liked`
  - EN: `"Dr. :name liked your post"`
  - AR: `"د. :name أعجب بمنشورك"`

- **Post Comments**: `api.notification_post_commented`
  - EN: `"Dr. :name commented on your post"`
  - AR: `"د. :name علق على منشورك"`

- **Comment Likes**: `api.notification_comment_liked`
  - EN: `"Dr. :name liked your comment"`
  - AR: `"د. :name أعجب بتعليقك"`

### **2. Group Management**
- **Group Invitations**: `api.notification_group_invitation`
  - EN: `"Dr. :name invited you to his group"`
  - AR: `"د. :name دعاك إلى مجموعته"`

- **Invitation Accepted**: `api.notification_group_invitation_accepted`
  - EN: `"Dr. :name accepted your group invitation"`
  - AR: `"د. :name قبل دعوتك للمجموعة"`

- **Join Requests**: `api.notification_group_join_request`
  - EN: `"Dr. :name requested to join group"`
  - AR: `"د. :name طلب الانضمام إلى المجموعة"`

### **3. Medical Workflow**
- **New Patients**: `api.notification_new_patient`
  - EN: `"Dr. :name created a new patient: :patient"`
  - AR: `"د. :name أنشأ مريضاً جديداً: :patient"`

- **Consultation Requests**: `api.notification_consultation_request`
  - EN: `"Dr. :name is seeking your advice for his patient"`
  - AR: `"د. :name يطلب مشورتك لمريضه"`

- **Consultation Replies**: `api.notification_consultation_reply`
  - EN: `"Dr. :name has replied to your consultation request. 📩"`
  - AR: `"د. :name رد على طلب استشارتك. 📩"`

### **4. System Notifications**
- **Comments**: `api.notification_new_comment`
  - EN: `"New comment was created"`
  - AR: `"تم إنشاء تعليق جديد"`

- **Syndicate Card Status**: `api.notification_syndicate_card_status`
  - EN: `":message"`
  - AR: `":message"`

## 🛠️ **LocalizedNotificationService**

### **Core Features:**
```php
$service = new LocalizedNotificationService();

// Get all notifications in user's preferred language
$notifications = $service->getAllNotifications('ar');

// Get unread notifications in specific locale
$unreadNotifications = $service->getNewNotifications('en');

// Mark notifications as read
$service->markAsRead($notificationId);
$service->markAllAsRead();
```

### **Automatic Locale Detection:**
1. **URL Parameter**: `?locale=ar`
2. **User's Saved Preference**: `Auth::user()->locale`
3. **Accept-Language Header**: Via middleware
4. **Default Fallback**: `config('app.locale')`

## 🔌 **API Endpoints**

### **Localized Notification Management:**

```http
GET /api/v1/notifications/localized?locale=ar
GET /api/v1/notifications/localized/new?locale=en
POST /api/v1/notifications/localized/{id}/read
POST /api/v1/notifications/localized/read-all
POST /api/v1/notifications/localized/test
```

### **Response Format:**
```json
{
  "success": true,
  "message": "تم استرداد الإشعارات بنجاح",
  "locale": "ar",
  "data": {
    "value": true,
    "unreadCount": 3,
    "data": [
      {
        "id": 123,
        "content": "Dr. Ahmed liked your post",
        "localized_content": "د. أحمد أعجب بمنشورك",
        "localization_key": "api.notification_post_liked",
        "localization_params": {"name": "Ahmed"},
        "read": false,
        "created_at": "2025-09-20T10:30:00Z"
      }
    ]
  }
}
```

## 🧪 **Testing the System**

### **1. Create Test Notification:**
```http
POST /api/v1/notifications/localized/test
Authorization: Bearer {token}
```

### **2. View in Different Languages:**
```http
GET /api/v1/notifications/localized?locale=en
GET /api/v1/notifications/localized?locale=ar
```

### **3. Expected Results:**
- **English**: `"Dr. Test User liked your post"`
- **Arabic**: `"د. Test User أعجب بمنشورك"`

## 🔄 **Migration Strategy**

### **Phase 1: Backward Compatibility**
- ✅ New notifications use localization system
- ✅ Old notifications still display using `content` field
- ✅ No breaking changes to existing API endpoints

### **Phase 2: Gradual Migration**
- Run migration to add localization fields
- Update notification creation across all services
- Test with mixed old/new notifications

### **Phase 3: Full Deployment**
- Deploy localized notification system
- Monitor notification display in both languages
- Gradually phase out old `content`-only notifications

## 📊 **Performance Considerations**

### **Database Optimization:**
- ✅ Index on `localization_key` for fast lookups
- ✅ JSON field for flexible parameter storage
- ✅ Minimal impact on existing queries

### **Caching Strategy:**
- Localized content can be cached per locale
- Translation keys cached in memory
- Minimal overhead for locale switching

## 🎯 **Benefits**

### **For Users:**
- ✅ **Native Language Experience**: Notifications in Arabic/English
- ✅ **Consistent Localization**: Matches app language preference
- ✅ **Real-time Language Switching**: Change language without restart

### **For Developers:**
- ✅ **Maintainable Code**: Centralized translation management
- ✅ **Scalable System**: Easy to add new languages
- ✅ **Type Safety**: Structured localization parameters

### **For Business:**
- ✅ **Global Reach**: Support for Arabic-speaking medical professionals
- ✅ **Professional Experience**: Localized medical terminology
- ✅ **User Retention**: Better engagement with native language support

## 🚀 **Future Enhancements**

### **Planned Features:**
1. **Rich Notifications**: Support for HTML/Markdown in localized content
2. **Notification Templates**: Reusable templates for common notification types
3. **A/B Testing**: Different notification formats per locale
4. **Analytics**: Track notification engagement by language
5. **Admin Panel**: Manage notification templates and translations

---

## 📝 **Implementation Summary**

The EGYAKIN database notification localization system provides:

✅ **Complete Localization**: All database notifications support English and Arabic  
✅ **Backward Compatibility**: Existing notifications continue to work  
✅ **Developer-Friendly**: Simple API for creating localized notifications  
✅ **User-Centric**: Automatic language detection and preference handling  
✅ **Production-Ready**: Robust error handling and fallback mechanisms  

**Your medical professionals can now receive notifications in their preferred language, creating a more inclusive and professional experience!** 🌍🏥
