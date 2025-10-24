# Permissions System Documentation

## 🚀 **Enhanced API Endpoints - NEW!**

### **✅ Login Response Now Includes Roles & Permissions**
```json
{
  "value": true,
  "message": "User logged in successfully",
  "token": "1|abc123...",
  "data": { /* user data */ },
  "roles": ["doctor"],
  "permissions": ["view-patients", "create-patients", "edit-patients"]
}
```

### **✅ User Endpoint Enhanced**
```http
GET /api/v2/user
```
**Now returns:** User data + roles + permissions for refreshing without re-login

### **✅ Permission Check Enhanced**
```http
POST /api/v2/checkPermission
```
**Now returns:** Complete roles and permissions list instead of just specific checks

---

## 📚 Documentation Index

This directory contains comprehensive documentation for the EGYAKIN application's permissions and role-based access control (RBAC) system.

---

## 📁 Available Documents

### 1. **COMPREHENSIVE_PERMISSIONS_GUIDE.md** ⭐ MAIN GUIDE
**Most Comprehensive Document - Start Here!**

Complete reference guide with:
- ✅ All 180+ permissions organized by category
- ✅ Detailed descriptions for each permission
- ✅ API endpoints affected by each permission
- ✅ 8 recommended roles with permission assignments
- ✅ Complete seeder script
- ✅ Implementation examples
- ✅ Best practices

**Use this when:** You need complete information about all available permissions and how to implement them.

**File:** [COMPREHENSIVE_PERMISSIONS_GUIDE.md](./COMPREHENSIVE_PERMISSIONS_GUIDE.md)

---

### 2. **PERMISSIONS_QUICK_REFERENCE.md** ⚡ QUICK START
**Quick Reference Guide**

Condensed reference with:
- ⚡ Quick start commands
- ⚡ Role comparison matrix
- ⚡ Permission counts by category
- ⚡ Common implementation patterns
- ⚡ API usage examples

**Use this when:** You need quick answers or a cheat sheet.

**File:** [PERMISSIONS_QUICK_REFERENCE.md](./PERMISSIONS_QUICK_REFERENCE.md)

---

### 3. **FLUTTER_ROLES_PERMISSIONS_GUIDE.md** 📱 FRONTEND
**Flutter Integration Guide - UPDATED!**

Frontend-focused documentation:
- 📱 **Enhanced Flutter implementation** with immediate permission access
- 📱 **Updated data models** for roles and permissions
- 📱 **State management** with UserState class
- 📱 **UI conditional rendering** examples
- 📱 **Permission-based navigation** patterns
- 📱 **Complete login flow** with roles/permissions
- 📱 **Best practices** and error handling
- 📱 **Testing strategies** for permission-based UI
- 📱 **Migration guide** from old to new system

**✅ NEW:** Login response now includes roles and permissions immediately!

**Use this when:** You're implementing Flutter frontend with role-based access control.

**File:** [FLUTTER_ROLES_PERMISSIONS_GUIDE.md](./FLUTTER_ROLES_PERMISSIONS_GUIDE.md)

---

### 4. **BACKEND_PERMISSION_ENHANCEMENTS.md** 🔧 BACKEND
**Backend Implementation Details**

Technical backend guide:
- 🔧 Laravel implementation patterns
- 🔧 Service layer modifications
- 🔧 Middleware setup
- 🔧 Controller examples
- 🔧 Testing strategies

**Use this when:** Modifying backend permission logic or adding new features.

**File:** [BACKEND_PERMISSION_ENHANCEMENTS.md](./BACKEND_PERMISSION_ENHANCEMENTS.md)

---

## 🚀 Quick Start

### Step 1: Seed Permissions and Roles
```bash
cd ~/public_html/test.egyakin.com
php artisan db:seed --class=RolePermissionSeeder
```

### Step 2: Assign Role to User (via API)
```bash
POST /api/v2/assignRoleToUser
{
  "user_id": 1,
  "role_name": "doctor"
}
```

### Step 3: Check User Permissions
```bash
GET /api/v2/user
# Returns user data with roles and permissions
```

---

## 📊 System Overview

### Total Permissions: **~180**

Organized into 18 categories:
1. **users** (14) - User management
2. **patients** (16) - Patient records
3. **medical** (16) - Medical data
4. **posts** (15) - Feed posts
5. **comments** (11) - Post comments
6. **groups** (16) - Groups management
7. **consultations** (10) - Medical consultations
8. **ai** (2) - AI features
9. **communication** (13) - Notifications, contacts
10. **polls** (5) - Voting features
11. **doses** (5) - Medication info
12. **achievements** (7) - Gamification
13. **reports** (5) - Analytics
14. **settings** (4) - Configuration
15. **roles** (11) - Permissions management
16. **media** (4) - File uploads
17. **sharing** (3) - URL sharing
18. **admin** (5) - Admin panel

---

## 👥 Available Roles

| Role | Users | Description |
|------|-------|-------------|
| **super-admin** | 1-2 | Full system access, all permissions |
| **admin** | 5-10 | System management, moderation, reports |
| **senior-doctor** | 20-50 | Full medical + advanced features |
| **doctor** | 1000+ | Standard medical practice (most common) |
| **junior-doctor** | 100-500 | Limited medical access, training |
| **moderator** | 3-5 | Content moderation only |
| **content-manager** | 2-5 | Non-medical content |
| **viewer** | Unlimited | Read-only access |

---

## 🎯 Common Use Cases

### 1. New Doctor Registration
```php
// Assign 'doctor' role after verification
$user->assignRole('doctor');
```

### 2. Promote to Senior Doctor
```php
// After 3+ years experience
$user->removeRole('doctor');
$user->assignRole('senior-doctor');
```

### 3. Add Content Moderator
```php
$user->assignRole('moderator');
```

### 4. Check Permission in Controller
```php
if ($user->can('delete-patients')) {
    // Allow deletion
}
```

### 5. Protect Route with Permission
```php
Route::middleware(['auth:sanctum', 'permission:create-patients'])
    ->post('/patient', [PatientsController::class, 'storePatient']);
```

---

## 📋 Permission Naming Convention

All permissions follow this format:
```
{action}-{resource}
```

### Actions:
- `view` - Read access
- `create` - Create new
- `edit` - Update existing
- `delete` - Remove
- `manage` - Full control
- `moderate` - Moderation powers
- `export` - Export data

### Examples:
- `view-patients` - Can view patient list
- `create-posts` - Can create feed posts
- `edit-users` - Can edit user profiles
- `delete-any-post` - Can delete any user's post (moderation)
- `moderate-comments` - Can moderate comments

---

## 🔍 Checking Permissions

### Via API
```bash
# Check specific permission
POST /api/v2/checkPermission
{
  "permission": "delete-patients"
}

# Response
{
  "value": true,
  "message": "User has permission"
}
```

### In Laravel Controller
```php
// Single permission
if (auth()->user()->can('create-patients')) {
    // Allow
}

// Multiple permissions (any)
if (auth()->user()->hasAnyPermission(['edit-patients', 'delete-patients'])) {
    // Allow
}

// Check role
if (auth()->user()->hasRole('admin')) {
    // Allow admin actions
}
```

### In Flutter
```dart
// Check from user data
bool canDeletePatients = user.permissions.contains('delete-patients');

if (canDeletePatients) {
  // Show delete button
}

// Or check role
bool isAdmin = user.roles.contains('admin');
```

---

## 🛠️ Maintenance

### Clear Permission Cache
```bash
php artisan permission:cache-reset
```

### View Permissions in Admin Panel
```
Navigate to: /admin/permissions
```

### View Roles and Assignments
```
Navigate to: /admin/roles
```

### Re-seed Permissions
```bash
php artisan db:seed --class=RolePermissionSeeder
```

---

## 🔐 Security Best Practices

1. **Limit Super Admins** - Only 1-2 trusted people
2. **Regular Audits** - Check role assignments monthly
3. **Principle of Least Privilege** - Give minimum required permissions
4. **Test in Development** - Always test permission changes before production
5. **Document Custom Permissions** - If you add new permissions, update docs
6. **Use Roles, Not Direct Permissions** - Assign roles to users, not individual permissions
7. **Monitor Admin Actions** - Enable audit logging for admin panel

---

## 📈 Extending the System

### Adding a New Permission

1. **Add to Seeder** (`database/seeders/RolePermissionSeeder.php`):
```php
['name' => 'new-permission', 'category' => 'category', 'description' => 'Description'],
```

2. **Run Seeder**:
```bash
php artisan db:seed --class=RolePermissionSeeder
```

3. **Assign to Roles** (in seeder or admin panel):
```php
$role->givePermissionTo('new-permission');
```

4. **Update Documentation**:
- Add to COMPREHENSIVE_PERMISSIONS_GUIDE.md
- Update permission count

### Adding a New Role

1. **Add to Seeder**:
```php
$newRole = Role::firstOrCreate(['name' => 'new-role']);
$newRole->syncPermissions([/* permissions */]);
```

2. **Run Seeder**:
```bash
php artisan db:seed --class=RolePermissionSeeder
```

3. **Document the Role**:
- Add to all permission documentation
- Update role comparison matrix

---

## 📞 Support & Questions

### For Technical Issues:
1. Check the comprehensive guide first
2. Review Laravel Spatie Permission docs: https://spatie.be/docs/laravel-permission
3. Check logs: `storage/logs/laravel.log`

### For Implementation Questions:
1. See BACKEND_PERMISSION_ENHANCEMENTS.md for backend
2. See FLUTTER_ROLES_PERMISSIONS_GUIDE.md for frontend
3. Check existing controllers for examples

### Testing Permissions:
1. Use Postman collection: `scripts/postman_collection.json`
2. Test in development environment first
3. Use `/api/v2/checkPermission` endpoint

---

## 📝 Files in This Directory

```
docs/api/permissions/
├── README.md                                 # This file
├── COMPREHENSIVE_PERMISSIONS_GUIDE.md        # Complete permissions list (MAIN)
├── PERMISSIONS_QUICK_REFERENCE.md            # Quick cheat sheet
├── FLUTTER_ROLES_PERMISSIONS_GUIDE.md        # Flutter implementation
├── BACKEND_PERMISSION_ENHANCEMENTS.md        # Backend technical guide
├── PERMISSIONS_IMPLEMENTATION_SUMMARY.md     # Implementation overview
└── PERMISSIONS_FLOW_DIAGRAM.md               # System flow diagrams
```

---

## 🔄 Version History

| Date | Version | Changes |
|------|---------|---------|
| 2025-10-08 | 2.0 | Complete overhaul: 180 permissions, 18 categories, 8 roles |
| 2025-09-06 | 1.5 | Added permission categories and descriptions |
| 2025-03-27 | 1.0 | Initial permission system with Spatie package |

---

## 📌 Related Files

### Seeder
- `database/seeders/RolePermissionSeeder.php` - Main seeder script

### Models
- `app/Models/User.php` - User model with HasRoles trait
- `app/Models/Permission.php` - Custom permission model
- `Spatie\Permission\Models\Role` - Role model

### Controllers
- `app/Modules/RolePermission/Controllers/RolePermissionController.php`
- `app/Http/Controllers/Api/V2/RolePermissionController.php`

### Config
- `config/permission.php` - Spatie permission configuration

### Migrations
- `database/migrations/2024_03_27_205813_create_permission_tables.php`
- `database/migrations/2025_09_06_000034_add_category_to_permissions_table.php`

### Routes
- `routes/api/v2.php` - API routes with permission middleware

---

**Last Updated**: October 8, 2025  
**Maintained By**: EGYAKIN Development Team  
**Version**: 2.0
