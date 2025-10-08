# Permissions Quick Reference

## 🚀 Quick Start

### Run the Seeder
```bash
php artisan db:seed --class=RolePermissionSeeder
```

### Assign Role via API
```bash
POST /api/v2/assignRoleToUser
{
  "user_id": 1,
  "role_name": "doctor"
}
```

---

## 📊 Overview

### Total Permissions: **~180**
### Total Roles: **8**
### Categories: **18**

---

## 👥 Available Roles

| Role | Description | Permission Count |
|------|-------------|-----------------|
| **super-admin** | Full system access | All (~180) |
| **admin** | System management & moderation | ~90 |
| **senior-doctor** | Full medical access + advanced features | ~70 |
| **doctor** | Standard medical practice | ~60 |
| **junior-doctor** | Limited medical access | ~30 |
| **moderator** | Content moderation | ~20 |
| **content-manager** | Non-medical content | ~25 |
| **viewer** | Read-only access | ~10 |

---

## 📁 Permission Categories

| Category | Permission Count | Examples |
|----------|-----------------|----------|
| **users** | 14 | `view-users`, `create-users`, `block-users` |
| **patients** | 16 | `view-patients`, `create-patients`, `export-patients` |
| **medical** | 16 | `view-questions`, `create-recommendations`, `view-scores` |
| **posts** | 15 | `view-posts`, `create-posts`, `moderate-posts` |
| **comments** | 11 | `view-comments`, `create-comments`, `moderate-comments` |
| **groups** | 16 | `view-groups`, `create-groups`, `invite-group-members` |
| **consultations** | 10 | `view-consultations`, `create-consultations`, `reply-consultations` |
| **ai** | 2 | `use-ai-consultation`, `view-ai-history` |
| **communication** | 13 | `view-notifications`, `send-push-notifications` |
| **polls** | 5 | `view-polls`, `vote-polls`, `create-polls` |
| **doses** | 5 | `view-doses`, `create-doses`, `search-doses` |
| **achievements** | 7 | `view-achievements`, `assign-achievements` |
| **reports** | 5 | `view-reports`, `export-patient-data`, `view-analytics` |
| **settings** | 4 | `view-settings`, `edit-settings`, `manage-app-settings` |
| **roles** | 11 | `view-roles`, `create-roles`, `assign-permissions` |
| **media** | 4 | `upload-images`, `upload-videos`, `delete-media` |
| **sharing** | 3 | `generate-share-urls`, `view-share-preview` |
| **admin** | 5 | `access-admin-panel`, `view-dashboard`, `view-audit-logs` |

---

## 🔑 Most Common Permissions

### Patient Management
```
view-patients
create-patients
edit-patients
delete-patients
view-all-patients
export-patients
```

### Content (Feed)
```
view-posts
create-posts
edit-posts
delete-posts
like-posts
create-comments
```

### Groups
```
view-groups
create-groups
join-groups
invite-group-members
```

### Consultations
```
view-consultations
create-consultations
reply-consultations
```

---

## 🛡️ Role Comparison Matrix

| Feature | Super Admin | Admin | Senior Doctor | Doctor | Junior Doctor | Moderator | Content Manager | Viewer |
|---------|------------|-------|---------------|--------|---------------|-----------|----------------|--------|
| **Patients** | Full | Full | Full | Full | View + Create | - | - | - |
| **Medical Data** | Full | Full | Full | View + Limited Edit | View Only | - | - | - |
| **Posts** | Full | Moderate | Full | Full | Full | Moderate | Full | View |
| **Groups** | Full | Full | Full | Full | Limited | View | Full | View |
| **Consultations** | Full | - | Full | Create + Reply | View Only | - | - | - |
| **Admin Panel** | Yes | Yes | - | - | - | - | - | - |
| **User Management** | Full | Full | - | - | - | Block Only | - | - |
| **Reports** | Full | Full | - | - | - | View | - | - |
| **AI Consultation** | Yes | - | Yes | Yes | - | - | - | - |

---

## 🔧 Implementation Examples

### Protect a Route
```php
Route::middleware(['auth:sanctum', 'permission:create-patients'])
    ->post('/patient', [PatientsController::class, 'storePatient']);
```

### Check in Controller
```php
if ($user->can('delete-patients')) {
    // Allow deletion
}
```

### Check Multiple Permissions
```php
if ($user->hasAnyPermission(['edit-patients', 'delete-patients'])) {
    // Allow action
}
```

### Check Role
```php
if ($user->hasRole('admin')) {
    // Admin-only action
}
```

### Check Role or Permission
```php
if ($user->hasRole('admin') || $user->can('moderate-posts')) {
    // Allow moderation
}
```

---

## 📝 Permission Naming Convention

```
{action}-{resource}
```

### Actions:
- `view` - Read access
- `create` - Create new records
- `edit` - Update existing records
- `delete` - Remove records
- `manage` - Full control
- `moderate` - Moderation powers
- `export` - Export data
- `assign` - Assign to users
- `upload` - Upload files

### Resources:
- Plural for collections: `users`, `patients`, `posts`
- Singular for specific actions: `patient-files`, `admin-panel`

---

## 🎯 Recommended Role Assignment

### New User Registration
- Default: **doctor** (if verified medical professional)
- Default: **viewer** (if pending verification)

### After Verification
- Upgrade to: **doctor**

### For Experienced Doctors (3+ years)
- Upgrade to: **senior-doctor**

### For Content Team
- Assign: **content-manager**

### For Support Team
- Assign: **moderator**

### For IT/System Admins
- Assign: **admin** or **super-admin**

---

## 🔍 Check User Permissions API

### Get User's Permissions
```bash
GET /api/v2/user
```

Response includes roles and permissions:
```json
{
  "id": 1,
  "name": "Dr. John Doe",
  "roles": ["doctor"],
  "permissions": ["view-patients", "create-patients", ...]
}
```

### Check Specific Permission
```bash
POST /api/v2/checkPermission
{
  "permission": "delete-patients"
}
```

Response:
```json
{
  "value": true,
  "message": "User has permission"
}
```

---

## 🚨 Important Notes

1. **Super Admin**: Should only be assigned to 1-2 trusted system administrators
2. **Admin**: For department heads and senior management
3. **Doctor**: Most common role for medical professionals
4. **Permissions are cached**: Clear cache after changes:
   ```bash
   php artisan permission:cache-reset
   ```
5. **Test thoroughly**: Always test permission changes in development first
6. **Audit regularly**: Check role assignments in admin panel regularly

---

## 📚 Related Documents

- [COMPREHENSIVE_PERMISSIONS_GUIDE.md](./COMPREHENSIVE_PERMISSIONS_GUIDE.md) - Full permissions list with descriptions
- [FLUTTER_ROLES_PERMISSIONS_GUIDE.md](./FLUTTER_ROLES_PERMISSIONS_GUIDE.md) - Flutter frontend integration
- [BACKEND_PERMISSION_ENHANCEMENTS.md](./BACKEND_PERMISSION_ENHANCEMENTS.md) - Backend implementation details

---

## 🔄 Update History

| Date | Version | Changes |
|------|---------|---------|
| 2025-10-08 | 2.0 | Complete permissions system with 180 permissions across 18 categories |
| 2025-09-06 | 1.0 | Initial permission system with basic roles |

---

**Last Updated**: October 8, 2025
**Total Permissions**: ~180
**Total Roles**: 8

