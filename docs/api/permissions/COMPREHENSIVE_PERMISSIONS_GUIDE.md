# Comprehensive Permissions Guide for EGYAKIN App

## 📋 Table of Contents
- [Overview](#overview)
- [Permission Structure](#permission-structure)
- [Complete Permissions List](#complete-permissions-list)
- [Recommended Roles](#recommended-roles)
- [Implementation Guide](#implementation-guide)
- [Seeder Script](#seeder-script)

---

## 🎯 Overview

This document provides a **complete list of all possible permissions** based on the EGYAKIN application's features and API endpoints. The permissions are organized by functional categories to make role assignment and management easier.

### Technology Stack
- **Backend**: Laravel with Spatie Laravel Permission package
- **Database**: MySQL with role-based access control tables
- **Authentication**: Laravel Sanctum (Token-based)

---

## 🏗️ Permission Structure

Each permission follows this naming convention:
```
{action}-{resource}
```

**Actions**: `view`, `create`, `edit`, `delete`, `export`, `moderate`, `manage`

**Categories**:
1. **User Management** - User accounts, profiles, doctors
2. **Patient Management** - Patient records, medical data, sections
3. **Medical Data** - Questions, sections, scores, recommendations
4. **Content Management** - Posts, comments, feed
5. **Group Management** - Groups, memberships, invitations
6. **Consultation Management** - Medical consultations, replies
7. **Communication** - Notifications, contacts, chat
8. **Reports & Analytics** - Data export, statistics, reports
9. **Settings** - System configuration
10. **Achievements** - Gamification and rewards
11. **Doses** - Medication information
12. **Polls** - Voting and survey management
13. **Admin Panel** - Filament admin interface

---

## 📜 Complete Permissions List

### 1️⃣ User Management (Category: `users`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-users` | View list of users/doctors | `GET /api/v2/users` |
| `view-user-profile` | View specific user profile | `GET /api/v2/users/{id}`, `GET /api/v2/showAnotherProfile/{id}` |
| `create-users` | Register new users | `POST /api/v2/register` |
| `edit-users` | Update user details | `PUT /api/v2/users`, `PUT /api/v2/users/{id}` |
| `delete-users` | Delete user accounts | `DELETE /api/v2/users/{id}` |
| `change-user-password` | Change user passwords | `POST /api/v2/changePassword` |
| `upload-profile-image` | Upload user profile pictures | `POST /api/v2/upload-profile-image` |
| `upload-syndicate-card` | Upload doctor verification documents | `POST /api/v2/uploadSyndicateCard` |
| `view-user-achievements` | View user achievements and scores | `GET /api/v2/users/{user}/achievements` |
| `view-doctor-patients` | View doctor's patient list | `GET /api/v2/doctorProfileGetPatients/{id}` |
| `view-doctor-score-history` | View doctor's scoring history | `GET /api/v2/doctorProfileGetScoreHistory/{id}` |
| `block-users` | Block/unblock user accounts | Admin Panel Only |
| `limit-users` | Limit user access | Admin Panel Only |
| `manage-user-locale` | Update user language preferences | `POST /api/v2/user/locale` |

---

### 2️⃣ Patient Management (Category: `patients`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-patients` | View patient list | `GET /api/v2/homeNew`, `GET /api/v2/allPatientsNew` |
| `view-patient-details` | View specific patient details | `GET /api/v2/patient/{section_id}/{patient_id}` |
| `create-patients` | Create new patient records | `POST /api/v2/patient` |
| `edit-patients` | Update patient information | `PUT /api/v2/patientsection/{section_id}/{patient_id}` |
| `delete-patients` | Delete patient records | `DELETE /api/v2/patient/{id}` |
| `search-patients` | Search for patients | `POST /api/v2/searchNew` |
| `view-current-patients` | View only assigned patients | `GET /api/v2/currentPatientsNew` |
| `view-all-patients` | View all patients in system | `GET /api/v2/allPatientsNew` |
| `mark-patients` | Bookmark/favorite patients | `POST /api/v2/markedPatients/{patient_id}`, `DELETE /api/v2/markedPatients/{patient_id}` |
| `view-marked-patients` | View bookmarked patients | `GET /api/v2/markedPatients` |
| `upload-patient-files` | Upload patient documents/files | `POST /api/v2/uploadFile`, `POST /api/v2/uploadFileNew` |
| `filter-patients` | Use advanced patient filters | `GET /api/v2/patientFilters`, `POST /api/v2/patientFilters` |
| `export-patients` | Export patient data | `POST /api/v2/exportFilteredPatients` |
| `generate-patient-pdf` | Generate patient PDF reports | `GET /api/v2/generatePDF/{patient_id}` |
| `submit-patient-sections` | Submit patient section data | `PUT /api/v2/submitStatus/{patient_id}` |
| `view-patient-sections` | View patient medical sections | `GET /api/v2/showSections/{patient_id}` |

---

### 3️⃣ Medical Data Management (Category: `medical`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-questions` | View medical questions | `GET /api/v2/questions`, `GET /api/v2/questions/{section_id}` |
| `create-questions` | Create new questions | `POST /api/v2/questions` |
| `edit-questions` | Update existing questions | `PUT /api/v2/questions/{id}` |
| `delete-questions` | Delete questions | `DELETE /api/v2/questions/{id}` |
| `view-sections` | View medical sections | `GET /api/v2/showSections/{patient_id}` |
| `create-sections` | Create medical sections | Admin Panel Only |
| `edit-sections` | Edit medical sections | Admin Panel Only |
| `delete-sections` | Delete medical sections | Admin Panel Only |
| `view-scores` | View patient scores | Admin Panel Only |
| `create-scores` | Create scoring entries | Admin Panel Only |
| `edit-scores` | Edit scoring entries | Admin Panel Only |
| `view-score-history` | View scoring history | Admin Panel Only |
| `view-recommendations` | View medical recommendations | `GET /api/v2/recommendations/{patient_id}` |
| `create-recommendations` | Create recommendations | `POST /api/v2/recommendations/{patient_id}` |
| `edit-recommendations` | Update recommendations | `PUT /api/v2/recommendations/{patient_id}` |
| `delete-recommendations` | Delete recommendations | `DELETE /api/v2/recommendations/{patient_id}` |

---

### 4️⃣ Content Management - Feed & Posts (Category: `posts`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-posts` | View feed posts | `GET /api/v2/feed/posts`, `GET /api/v2/feed/posts/{id}` |
| `create-posts` | Create new posts | `POST /api/v2/feed/posts` |
| `edit-posts` | Edit own posts | `POST /api/v2/feed/posts/{id}` |
| `delete-posts` | Delete own posts | `DELETE /api/v2/feed/posts/{id}` |
| `edit-any-post` | Edit any user's post | Admin Panel Only |
| `delete-any-post` | Delete any user's post | Admin Panel Only |
| `moderate-posts` | Moderate user-generated posts | Admin Panel Only |
| `like-posts` | Like/unlike posts | `POST /api/v2/feed/posts/{id}/likeOrUnlikePost` |
| `save-posts` | Save/bookmark posts | `POST /api/v2/feed/posts/{id}/saveOrUnsavePost` |
| `view-post-likes` | View who liked a post | `GET /api/v2/posts/{postId}/likes` |
| `view-trending-posts` | View trending posts | `GET /api/v2/feed/trendingPosts` |
| `search-posts` | Search posts | `POST /api/v2/feed/searchPosts` |
| `search-hashtags` | Search by hashtags | `POST /api/v2/feed/searchHashtags` |
| `view-doctor-posts` | View posts by specific doctor | `GET /api/v2/doctorposts/{doctorId}` |
| `view-saved-posts` | View own saved posts | `GET /api/v2/doctorsavedposts/{doctorId}` |

---

### 5️⃣ Comments Management (Category: `comments`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-comments` | View post comments | `GET /api/v2/posts/{postId}/comments` |
| `create-comments` | Add comments to posts | `POST /api/v2/feed/posts/{id}/comment` |
| `edit-comments` | Edit own comments | Admin Panel Only |
| `delete-comments` | Delete own comments | `DELETE /api/v2/feed/comments/{id}` |
| `delete-any-comment` | Delete any user's comment | Admin Panel Only |
| `like-comments` | Like/unlike comments | `POST /api/v2/comments/{commentId}/likeOrUnlikeComment` |
| `moderate-comments` | Moderate user comments | Admin Panel Only |
| `view-patient-comments` | View patient-related comments | `GET /api/v2/comment/{patient_id}` |
| `create-patient-comments` | Add comments to patients | `POST /api/v2/comment` |
| `edit-patient-comments` | Edit patient comments | `PUT /api/v2/comment/{patient_id}` |
| `delete-patient-comments` | Delete patient comments | `DELETE /api/v2/comment/{patient_id}` |

---

### 6️⃣ Group Management (Category: `groups`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-groups` | View all groups | `GET /api/v2/groups` |
| `view-group-details` | View specific group details | `GET /api/v2/groups/{id}` |
| `create-groups` | Create new groups | `POST /api/v2/groups` |
| `edit-groups` | Edit own groups | `POST /api/v2/groups/{id}` |
| `delete-groups` | Delete own groups | `DELETE /api/v2/groups/{id}` |
| `delete-any-group` | Delete any group | Admin Panel Only |
| `join-groups` | Join public groups | `POST /api/v2/groups/{groupId}/join` |
| `leave-groups` | Leave groups | `POST /api/v2/groups/{groupId}/leave` |
| `view-my-groups` | View own group memberships | `GET /api/v2/mygroups` |
| `invite-group-members` | Invite users to groups | `POST /api/v2/groups/{groupId}/invite` |
| `remove-group-members` | Remove members from own groups | `POST /api/v2/groups/{groupId}/removeMember` |
| `handle-group-invitations` | Accept/reject group invitations | `POST /api/v2/groups/{groupId}/invitation` |
| `handle-join-requests` | Approve/reject join requests | `POST /api/v2/groups/{groupId}/join-request` |
| `view-group-members` | View group member list | `GET /api/v2/groups/{groupId}/members` |
| `search-group-members` | Search within group members | `POST /api/v2/groups/{groupId}/searchMembers` |
| `view-group-invitations` | View pending invitations | `GET /api/v2/groups/invitations/{doctorId}` |

---

### 7️⃣ Consultation Management (Category: `consultations`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-consultations` | View consultations | `GET /api/v2/consultations/sent`, `GET /api/v2/consultations/received` |
| `create-consultations` | Create consultation requests | `POST /api/v2/consultations` |
| `view-consultation-details` | View specific consultation | `GET /api/v2/consultations/{id}` |
| `edit-consultations` | Update consultation status | `PUT /api/v2/consultations/{id}` |
| `add-consultation-doctors` | Add doctors to consultations | `POST /api/v2/consultations/{id}/add-doctors` |
| `remove-consultation-doctors` | Remove doctors from consultations | `DELETE /api/v2/consultations/{consultationId}/doctors/{doctorId}` |
| `toggle-consultation-status` | Change consultation status | `PUT /api/v2/consultations/{id}/toggle-status` |
| `view-consultation-members` | View consultation participants | `GET /api/v2/consultations/{id}/members` |
| `reply-consultations` | Add replies to consultations | `POST /api/v2/consultations/{id}/replies` |
| `search-consultation-doctors` | Search for doctors to consult | `POST /api/v2/consultationDoctorSearch/{data}` |

---

### 8️⃣ AI Chat & Assistance (Category: `ai`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `use-ai-consultation` | Use AI consultation feature | `POST /api/v2/AIconsultation/{patientId}` |
| `view-ai-history` | View AI consultation history | `GET /api/v2/AIconsultation-history/{patientId}` |

---

### 9️⃣ Communication (Category: `communication`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-notifications` | View own notifications | `GET /api/v2/notifications/localized` |
| `view-new-notifications` | View new notifications | `GET /api/v2/notifications/localized/new` |
| `mark-notification-read` | Mark notifications as read | `POST /api/v2/notifications/localized/{id}/read` |
| `mark-all-notifications-read` | Mark all notifications read | `POST /api/v2/notifications/localized/read-all` |
| `create-notifications` | Create system notifications | `POST /api/v2/notification` |
| `delete-notifications` | Delete notifications | `DELETE /api/v2/notification/{id}` |
| `send-push-notifications` | Send push notifications | `POST /api/v2/send-notification` |
| `send-bulk-push-notifications` | Send bulk push notifications | `POST /api/v2/sendAllPushNotification` |
| `manage-fcm-tokens` | Manage FCM device tokens | `POST /api/v2/storeFCM` |
| `view-contacts` | View contact requests | `GET /api/v2/contact` |
| `create-contacts` | Submit contact requests | `POST /api/v2/contact` |
| `edit-contacts` | Update contact requests | `PUT /api/v2/contact/{id}` |
| `delete-contacts` | Delete contact requests | `DELETE /api/v2/contact/{id}` |

---

### 🔟 Polls Management (Category: `polls`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-polls` | View polls in posts | Included in post viewing |
| `create-polls` | Create polls in posts | Included in post creation |
| `vote-polls` | Vote in polls | `POST /api/v2/polls/{pollId}/vote` |
| `view-poll-voters` | View who voted for options | `GET /api/v2/polls/{pollId}/options/{optionId}/voters` |
| `add-poll-options` | Add new poll options | `POST /api/v2/polls/{pollId}/options` |

---

### 1️⃣1️⃣ Dose Management (Category: `doses`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-doses` | View medication dose information | `GET /api/v2/dose`, `GET /api/v2/dose/{id}` |
| `create-doses` | Add new dose entries | `POST /api/v2/dose` |
| `edit-doses` | Update dose information | `PUT /api/v2/dose/{id}` |
| `delete-doses` | Delete dose entries | `DELETE /api/v2/dose/{id}` |
| `search-doses` | Search dose database | `GET /api/v2/dose/search/{query}` |

---

### 1️⃣2️⃣ Achievements & Gamification (Category: `achievements`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-achievements` | View all achievements | `GET /api/v2/achievement`, `GET /api/v2/achievements` |
| `view-achievement-details` | View specific achievement | `GET /api/v2/achievement/{id}` |
| `create-achievements` | Create new achievements | `POST /api/v2/achievement`, `POST /api/v2/achievements` |
| `edit-achievements` | Update achievements | `PUT /api/v2/achievement/{id}` |
| `delete-achievements` | Delete achievements | `DELETE /api/v2/achievement/{id}` |
| `view-user-achievements` | View user's earned achievements | `GET /api/v2/users/{user}/achievements` |
| `assign-achievements` | Manually assign achievements | `POST /api/v2/checkAndAssignAchievementsForAllUsers` |

---

### 1️⃣3️⃣ Reports & Analytics (Category: `reports`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-reports` | Access reports section | Admin Panel Only |
| `export-patient-data` | Export patient data | `POST /api/v2/exportFilteredPatients` |
| `export-filtered-patients` | Export with filters | `POST /api/v2/exportFilteredPatients` |
| `view-analytics` | View system analytics | Admin Panel Only |
| `view-statistics` | View usage statistics | Admin Panel Only |

---

### 1️⃣4️⃣ Settings & Configuration (Category: `settings`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-settings` | View system settings | `GET /api/v2/settings` |
| `edit-settings` | Update system settings | `PUT /api/v2/settings/{settings}` |
| `delete-settings` | Delete settings | `DELETE /api/v2/settings/{settings}` |
| `manage-app-settings` | Full settings management | Admin Panel Only |

---

### 1️⃣5️⃣ Roles & Permissions (Category: `roles`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `view-roles` | View all roles | Admin Panel Only |
| `create-roles` | Create new roles | `POST /api/v2/createRoleAndPermission` |
| `edit-roles` | Edit existing roles | Admin Panel Only |
| `delete-roles` | Delete roles | Admin Panel Only |
| `view-permissions` | View all permissions | Admin Panel Only |
| `create-permissions` | Create new permissions | `POST /api/v2/createRoleAndPermission` |
| `edit-permissions` | Edit permissions | Admin Panel Only |
| `delete-permissions` | Delete permissions | Admin Panel Only |
| `assign-roles` | Assign roles to users | `POST /api/v2/assignRoleToUser` |
| `assign-permissions` | Assign permissions to users/roles | `POST /api/v2/assignRoleToUser` |
| `check-permissions` | Check user permissions | `POST /api/v2/checkPermission` |

---

### 1️⃣6️⃣ Media & File Management (Category: `media`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `upload-images` | Upload images | `POST /api/v2/uploadImage` |
| `upload-videos` | Upload videos | `POST /api/v2/uploadVideo` |
| `upload-files` | Upload general files | `POST /api/v2/uploadFile` |
| `delete-media` | Delete uploaded media | Varies by context |

---

### 1️⃣7️⃣ Share & URL Management (Category: `sharing`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `generate-share-urls` | Generate shareable URLs | `POST /api/v2/share/generate` |
| `generate-bulk-share-urls` | Generate multiple share URLs | `POST /api/v2/share/bulk` |
| `view-share-preview` | View share URL previews | `GET /api/v2/share/preview` |

---

### 1️⃣8️⃣ Admin Panel Access (Category: `admin`)

| Permission Name | Description | Endpoints Affected |
|----------------|-------------|-------------------|
| `access-admin-panel` | Access Filament admin panel | `/admin` |
| `view-dashboard` | View admin dashboard | `/admin` |
| `view-audit-logs` | View system audit logs | Admin Panel Only |
| `export-audit-logs` | Export audit logs | Admin Panel Only |
| `manage-system-health` | View system health metrics | Admin Panel Only |

---

## 👥 Recommended Roles

### 1. Super Admin
**Full System Access**
```
All permissions
```

### 2. Admin
**System Management & Moderation**
- All user management permissions
- All patient management permissions
- All medical data permissions
- Moderate posts and comments
- View reports and analytics
- Manage settings
- Access admin panel

### 3. Senior Doctor
**Full Medical Access + Limited Admin**
- All patient management permissions
- All medical data permissions
- Create consultations
- Use AI consultation
- View reports
- Create posts and groups
- Manage achievements

### 4. Doctor (Standard)
**Medical Practice & Community**
- View/create/edit/delete own patients
- View all patients (read-only for others)
- View/create/edit medical data
- Create and respond to consultations
- Create posts and comments
- Join/create groups
- Use AI consultation
- View notifications

### 5. Junior Doctor / Resident
**Limited Medical Access**
- View patients
- Create patients (with approval)
- View medical data
- View consultations
- Create posts and comments
- Join groups
- View notifications

### 6. Moderator
**Content Management**
- Moderate posts
- Moderate comments
- Delete inappropriate content
- View reports
- Send notifications
- Block users (temporary)

### 7. Content Manager
**Non-Medical Content**
- Create/edit/delete posts
- Manage groups
- Send notifications
- View analytics

### 8. Viewer / Guest Doctor
**Read-Only Access**
- View posts
- View public groups
- View notifications
- View trending content

---

## 🚀 Implementation Guide

### Step 1: Run the Seeder

```bash
# Seed all permissions and roles
php artisan db:seed --class=RolePermissionSeeder

# Or refresh and seed
php artisan migrate:fresh --seed
```

### Step 2: Assign Roles to Users

Via API:
```bash
POST /api/v2/assignRoleToUser
{
  "user_id": 1,
  "role_name": "doctor"
}
```

Via Filament Admin Panel:
1. Go to `/admin/roles`
2. Edit a role
3. Assign permissions
4. Save

### Step 3: Protect Routes

In your routes file:
```php
Route::middleware(['auth:sanctum', 'permission:create-patients'])
    ->post('/patient', [PatientsController::class, 'storePatient']);
```

### Step 4: Check Permissions in Controllers

```php
// Check single permission
if ($user->can('create-patients')) {
    // Allow action
}

// Check multiple permissions (any)
if ($user->hasAnyPermission(['edit-patients', 'delete-patients'])) {
    // Allow action
}

// Check role
if ($user->hasRole('admin')) {
    // Allow admin action
}
```

---

## 📝 Seeder Script

Complete seeder script with all permissions and roles:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions with categories
        $permissions = [
            // User Management
            ['name' => 'view-users', 'category' => 'users', 'description' => 'View list of users'],
            ['name' => 'view-user-profile', 'category' => 'users', 'description' => 'View user profiles'],
            ['name' => 'create-users', 'category' => 'users', 'description' => 'Register new users'],
            ['name' => 'edit-users', 'category' => 'users', 'description' => 'Edit user details'],
            ['name' => 'delete-users', 'category' => 'users', 'description' => 'Delete users'],
            ['name' => 'change-user-password', 'category' => 'users', 'description' => 'Change user passwords'],
            ['name' => 'upload-profile-image', 'category' => 'users', 'description' => 'Upload profile images'],
            ['name' => 'upload-syndicate-card', 'category' => 'users', 'description' => 'Upload syndicate cards'],
            ['name' => 'view-user-achievements', 'category' => 'users', 'description' => 'View user achievements'],
            ['name' => 'view-doctor-patients', 'category' => 'users', 'description' => 'View doctor patient lists'],
            ['name' => 'view-doctor-score-history', 'category' => 'users', 'description' => 'View doctor scores'],
            ['name' => 'block-users', 'category' => 'users', 'description' => 'Block/unblock users'],
            ['name' => 'limit-users', 'category' => 'users', 'description' => 'Limit user access'],
            ['name' => 'manage-user-locale', 'category' => 'users', 'description' => 'Manage user language'],

            // Patient Management
            ['name' => 'view-patients', 'category' => 'patients', 'description' => 'View patient list'],
            ['name' => 'view-patient-details', 'category' => 'patients', 'description' => 'View patient details'],
            ['name' => 'create-patients', 'category' => 'patients', 'description' => 'Create patient records'],
            ['name' => 'edit-patients', 'category' => 'patients', 'description' => 'Edit patient records'],
            ['name' => 'delete-patients', 'category' => 'patients', 'description' => 'Delete patient records'],
            ['name' => 'search-patients', 'category' => 'patients', 'description' => 'Search patients'],
            ['name' => 'view-current-patients', 'category' => 'patients', 'description' => 'View assigned patients'],
            ['name' => 'view-all-patients', 'category' => 'patients', 'description' => 'View all patients'],
            ['name' => 'mark-patients', 'category' => 'patients', 'description' => 'Bookmark patients'],
            ['name' => 'view-marked-patients', 'category' => 'patients', 'description' => 'View bookmarked patients'],
            ['name' => 'upload-patient-files', 'category' => 'patients', 'description' => 'Upload patient files'],
            ['name' => 'filter-patients', 'category' => 'patients', 'description' => 'Filter patient lists'],
            ['name' => 'export-patients', 'category' => 'patients', 'description' => 'Export patient data'],
            ['name' => 'generate-patient-pdf', 'category' => 'patients', 'description' => 'Generate patient PDFs'],
            ['name' => 'submit-patient-sections', 'category' => 'patients', 'description' => 'Submit patient sections'],
            ['name' => 'view-patient-sections', 'category' => 'patients', 'description' => 'View patient sections'],

            // Medical Data
            ['name' => 'view-questions', 'category' => 'medical', 'description' => 'View medical questions'],
            ['name' => 'create-questions', 'category' => 'medical', 'description' => 'Create questions'],
            ['name' => 'edit-questions', 'category' => 'medical', 'description' => 'Edit questions'],
            ['name' => 'delete-questions', 'category' => 'medical', 'description' => 'Delete questions'],
            ['name' => 'view-sections', 'category' => 'medical', 'description' => 'View medical sections'],
            ['name' => 'create-sections', 'category' => 'medical', 'description' => 'Create sections'],
            ['name' => 'edit-sections', 'category' => 'medical', 'description' => 'Edit sections'],
            ['name' => 'delete-sections', 'category' => 'medical', 'description' => 'Delete sections'],
            ['name' => 'view-scores', 'category' => 'medical', 'description' => 'View scores'],
            ['name' => 'create-scores', 'category' => 'medical', 'description' => 'Create scores'],
            ['name' => 'edit-scores', 'category' => 'medical', 'description' => 'Edit scores'],
            ['name' => 'view-score-history', 'category' => 'medical', 'description' => 'View score history'],
            ['name' => 'view-recommendations', 'category' => 'medical', 'description' => 'View recommendations'],
            ['name' => 'create-recommendations', 'category' => 'medical', 'description' => 'Create recommendations'],
            ['name' => 'edit-recommendations', 'category' => 'medical', 'description' => 'Edit recommendations'],
            ['name' => 'delete-recommendations', 'category' => 'medical', 'description' => 'Delete recommendations'],

            // Content Management - Posts
            ['name' => 'view-posts', 'category' => 'posts', 'description' => 'View posts'],
            ['name' => 'create-posts', 'category' => 'posts', 'description' => 'Create posts'],
            ['name' => 'edit-posts', 'category' => 'posts', 'description' => 'Edit own posts'],
            ['name' => 'delete-posts', 'category' => 'posts', 'description' => 'Delete own posts'],
            ['name' => 'edit-any-post', 'category' => 'posts', 'description' => 'Edit any post'],
            ['name' => 'delete-any-post', 'category' => 'posts', 'description' => 'Delete any post'],
            ['name' => 'moderate-posts', 'category' => 'posts', 'description' => 'Moderate posts'],
            ['name' => 'like-posts', 'category' => 'posts', 'description' => 'Like/unlike posts'],
            ['name' => 'save-posts', 'category' => 'posts', 'description' => 'Save/bookmark posts'],
            ['name' => 'view-post-likes', 'category' => 'posts', 'description' => 'View post likes'],
            ['name' => 'view-trending-posts', 'category' => 'posts', 'description' => 'View trending posts'],
            ['name' => 'search-posts', 'category' => 'posts', 'description' => 'Search posts'],
            ['name' => 'search-hashtags', 'category' => 'posts', 'description' => 'Search hashtags'],
            ['name' => 'view-doctor-posts', 'category' => 'posts', 'description' => 'View doctor posts'],
            ['name' => 'view-saved-posts', 'category' => 'posts', 'description' => 'View saved posts'],

            // Comments
            ['name' => 'view-comments', 'category' => 'comments', 'description' => 'View comments'],
            ['name' => 'create-comments', 'category' => 'comments', 'description' => 'Create comments'],
            ['name' => 'edit-comments', 'category' => 'comments', 'description' => 'Edit own comments'],
            ['name' => 'delete-comments', 'category' => 'comments', 'description' => 'Delete own comments'],
            ['name' => 'delete-any-comment', 'category' => 'comments', 'description' => 'Delete any comment'],
            ['name' => 'like-comments', 'category' => 'comments', 'description' => 'Like comments'],
            ['name' => 'moderate-comments', 'category' => 'comments', 'description' => 'Moderate comments'],
            ['name' => 'view-patient-comments', 'category' => 'comments', 'description' => 'View patient comments'],
            ['name' => 'create-patient-comments', 'category' => 'comments', 'description' => 'Create patient comments'],
            ['name' => 'edit-patient-comments', 'category' => 'comments', 'description' => 'Edit patient comments'],
            ['name' => 'delete-patient-comments', 'category' => 'comments', 'description' => 'Delete patient comments'],

            // Groups
            ['name' => 'view-groups', 'category' => 'groups', 'description' => 'View groups'],
            ['name' => 'view-group-details', 'category' => 'groups', 'description' => 'View group details'],
            ['name' => 'create-groups', 'category' => 'groups', 'description' => 'Create groups'],
            ['name' => 'edit-groups', 'category' => 'groups', 'description' => 'Edit own groups'],
            ['name' => 'delete-groups', 'category' => 'groups', 'description' => 'Delete own groups'],
            ['name' => 'delete-any-group', 'category' => 'groups', 'description' => 'Delete any group'],
            ['name' => 'join-groups', 'category' => 'groups', 'description' => 'Join groups'],
            ['name' => 'leave-groups', 'category' => 'groups', 'description' => 'Leave groups'],
            ['name' => 'view-my-groups', 'category' => 'groups', 'description' => 'View my groups'],
            ['name' => 'invite-group-members', 'category' => 'groups', 'description' => 'Invite members'],
            ['name' => 'remove-group-members', 'category' => 'groups', 'description' => 'Remove members'],
            ['name' => 'handle-group-invitations', 'category' => 'groups', 'description' => 'Handle invitations'],
            ['name' => 'handle-join-requests', 'category' => 'groups', 'description' => 'Handle join requests'],
            ['name' => 'view-group-members', 'category' => 'groups', 'description' => 'View members'],
            ['name' => 'search-group-members', 'category' => 'groups', 'description' => 'Search members'],
            ['name' => 'view-group-invitations', 'category' => 'groups', 'description' => 'View invitations'],

            // Consultations
            ['name' => 'view-consultations', 'category' => 'consultations', 'description' => 'View consultations'],
            ['name' => 'create-consultations', 'category' => 'consultations', 'description' => 'Create consultations'],
            ['name' => 'view-consultation-details', 'category' => 'consultations', 'description' => 'View details'],
            ['name' => 'edit-consultations', 'category' => 'consultations', 'description' => 'Edit consultations'],
            ['name' => 'add-consultation-doctors', 'category' => 'consultations', 'description' => 'Add doctors'],
            ['name' => 'remove-consultation-doctors', 'category' => 'consultations', 'description' => 'Remove doctors'],
            ['name' => 'toggle-consultation-status', 'category' => 'consultations', 'description' => 'Change status'],
            ['name' => 'view-consultation-members', 'category' => 'consultations', 'description' => 'View members'],
            ['name' => 'reply-consultations', 'category' => 'consultations', 'description' => 'Add replies'],
            ['name' => 'search-consultation-doctors', 'category' => 'consultations', 'description' => 'Search doctors'],

            // AI Chat
            ['name' => 'use-ai-consultation', 'category' => 'ai', 'description' => 'Use AI consultation'],
            ['name' => 'view-ai-history', 'category' => 'ai', 'description' => 'View AI history'],

            // Communication
            ['name' => 'view-notifications', 'category' => 'communication', 'description' => 'View notifications'],
            ['name' => 'view-new-notifications', 'category' => 'communication', 'description' => 'View new notifications'],
            ['name' => 'mark-notification-read', 'category' => 'communication', 'description' => 'Mark as read'],
            ['name' => 'mark-all-notifications-read', 'category' => 'communication', 'description' => 'Mark all as read'],
            ['name' => 'create-notifications', 'category' => 'communication', 'description' => 'Create notifications'],
            ['name' => 'delete-notifications', 'category' => 'communication', 'description' => 'Delete notifications'],
            ['name' => 'send-push-notifications', 'category' => 'communication', 'description' => 'Send push notifications'],
            ['name' => 'send-bulk-push-notifications', 'category' => 'communication', 'description' => 'Send bulk push'],
            ['name' => 'manage-fcm-tokens', 'category' => 'communication', 'description' => 'Manage FCM tokens'],
            ['name' => 'view-contacts', 'category' => 'communication', 'description' => 'View contacts'],
            ['name' => 'create-contacts', 'category' => 'communication', 'description' => 'Create contacts'],
            ['name' => 'edit-contacts', 'category' => 'communication', 'description' => 'Edit contacts'],
            ['name' => 'delete-contacts', 'category' => 'communication', 'description' => 'Delete contacts'],

            // Polls
            ['name' => 'view-polls', 'category' => 'polls', 'description' => 'View polls'],
            ['name' => 'create-polls', 'category' => 'polls', 'description' => 'Create polls'],
            ['name' => 'vote-polls', 'category' => 'polls', 'description' => 'Vote in polls'],
            ['name' => 'view-poll-voters', 'category' => 'polls', 'description' => 'View voters'],
            ['name' => 'add-poll-options', 'category' => 'polls', 'description' => 'Add poll options'],

            // Doses
            ['name' => 'view-doses', 'category' => 'doses', 'description' => 'View doses'],
            ['name' => 'create-doses', 'category' => 'doses', 'description' => 'Create doses'],
            ['name' => 'edit-doses', 'category' => 'doses', 'description' => 'Edit doses'],
            ['name' => 'delete-doses', 'category' => 'doses', 'description' => 'Delete doses'],
            ['name' => 'search-doses', 'category' => 'doses', 'description' => 'Search doses'],

            // Achievements
            ['name' => 'view-achievements', 'category' => 'achievements', 'description' => 'View achievements'],
            ['name' => 'view-achievement-details', 'category' => 'achievements', 'description' => 'View details'],
            ['name' => 'create-achievements', 'category' => 'achievements', 'description' => 'Create achievements'],
            ['name' => 'edit-achievements', 'category' => 'achievements', 'description' => 'Edit achievements'],
            ['name' => 'delete-achievements', 'category' => 'achievements', 'description' => 'Delete achievements'],
            ['name' => 'view-user-achievements', 'category' => 'achievements', 'description' => 'View user achievements'],
            ['name' => 'assign-achievements', 'category' => 'achievements', 'description' => 'Assign achievements'],

            // Reports & Analytics
            ['name' => 'view-reports', 'category' => 'reports', 'description' => 'View reports'],
            ['name' => 'export-patient-data', 'category' => 'reports', 'description' => 'Export patient data'],
            ['name' => 'export-filtered-patients', 'category' => 'reports', 'description' => 'Export filtered data'],
            ['name' => 'view-analytics', 'category' => 'reports', 'description' => 'View analytics'],
            ['name' => 'view-statistics', 'category' => 'reports', 'description' => 'View statistics'],

            // Settings
            ['name' => 'view-settings', 'category' => 'settings', 'description' => 'View settings'],
            ['name' => 'edit-settings', 'category' => 'settings', 'description' => 'Edit settings'],
            ['name' => 'delete-settings', 'category' => 'settings', 'description' => 'Delete settings'],
            ['name' => 'manage-app-settings', 'category' => 'settings', 'description' => 'Manage app settings'],

            // Roles & Permissions
            ['name' => 'view-roles', 'category' => 'roles', 'description' => 'View roles'],
            ['name' => 'create-roles', 'category' => 'roles', 'description' => 'Create roles'],
            ['name' => 'edit-roles', 'category' => 'roles', 'description' => 'Edit roles'],
            ['name' => 'delete-roles', 'category' => 'roles', 'description' => 'Delete roles'],
            ['name' => 'view-permissions', 'category' => 'roles', 'description' => 'View permissions'],
            ['name' => 'create-permissions', 'category' => 'roles', 'description' => 'Create permissions'],
            ['name' => 'edit-permissions', 'category' => 'roles', 'description' => 'Edit permissions'],
            ['name' => 'delete-permissions', 'category' => 'roles', 'description' => 'Delete permissions'],
            ['name' => 'assign-roles', 'category' => 'roles', 'description' => 'Assign roles'],
            ['name' => 'assign-permissions', 'category' => 'roles', 'description' => 'Assign permissions'],
            ['name' => 'check-permissions', 'category' => 'roles', 'description' => 'Check permissions'],

            // Media
            ['name' => 'upload-images', 'category' => 'media', 'description' => 'Upload images'],
            ['name' => 'upload-videos', 'category' => 'media', 'description' => 'Upload videos'],
            ['name' => 'upload-files', 'category' => 'media', 'description' => 'Upload files'],
            ['name' => 'delete-media', 'category' => 'media', 'description' => 'Delete media'],

            // Sharing
            ['name' => 'generate-share-urls', 'category' => 'sharing', 'description' => 'Generate share URLs'],
            ['name' => 'generate-bulk-share-urls', 'category' => 'sharing', 'description' => 'Generate bulk URLs'],
            ['name' => 'view-share-preview', 'category' => 'sharing', 'description' => 'View share preview'],

            // Admin Panel
            ['name' => 'access-admin-panel', 'category' => 'admin', 'description' => 'Access admin panel'],
            ['name' => 'view-dashboard', 'category' => 'admin', 'description' => 'View dashboard'],
            ['name' => 'view-audit-logs', 'category' => 'admin', 'description' => 'View audit logs'],
            ['name' => 'export-audit-logs', 'category' => 'admin', 'description' => 'Export audit logs'],
            ['name' => 'manage-system-health', 'category' => 'admin', 'description' => 'Manage system health'],
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                [
                    'guard_name' => 'web',
                    'category' => $permission['category'],
                    'description' => $permission['description']
                ]
            );
        }

        $this->command->info('Permissions created successfully!');

        // Create roles and assign permissions
        $this->createRoles();
    }

    private function createRoles(): void
    {
        // Super Admin - All permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());
        $this->command->info('Super Admin role created with all permissions!');

        // Admin Role
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            // User management
            'view-users', 'view-user-profile', 'create-users', 'edit-users', 
            'block-users', 'limit-users', 'view-user-achievements',
            'view-doctor-patients', 'view-doctor-score-history',
            
            // Patient management (full)
            'view-patients', 'view-patient-details', 'create-patients', 'edit-patients', 
            'delete-patients', 'search-patients', 'view-current-patients', 'view-all-patients',
            'mark-patients', 'view-marked-patients', 'upload-patient-files', 'filter-patients',
            'export-patients', 'generate-patient-pdf', 'submit-patient-sections', 'view-patient-sections',
            
            // Medical data (full)
            'view-questions', 'create-questions', 'edit-questions', 'delete-questions',
            'view-sections', 'create-sections', 'edit-sections', 'delete-sections',
            'view-scores', 'create-scores', 'edit-scores', 'view-score-history',
            'view-recommendations', 'create-recommendations', 'edit-recommendations', 'delete-recommendations',
            
            // Content moderation
            'view-posts', 'edit-any-post', 'delete-any-post', 'moderate-posts',
            'delete-any-comment', 'moderate-comments',
            'delete-any-group',
            
            // Reports
            'view-reports', 'export-patient-data', 'view-analytics', 'view-statistics',
            
            // Settings
            'view-settings', 'edit-settings', 'manage-app-settings',
            
            // Admin panel
            'access-admin-panel', 'view-dashboard', 'view-audit-logs',
            
            // Communication
            'send-push-notifications', 'send-bulk-push-notifications', 'create-notifications',
        ]);
        $this->command->info('Admin role created!');

        // Senior Doctor Role
        $seniorDoctor = Role::firstOrCreate(['name' => 'senior-doctor']);
        $seniorDoctor->givePermissionTo([
            // Patient management (full)
            'view-patients', 'view-patient-details', 'create-patients', 'edit-patients', 
            'delete-patients', 'search-patients', 'view-current-patients', 'view-all-patients',
            'mark-patients', 'view-marked-patients', 'upload-patient-files', 'filter-patients',
            'export-patients', 'generate-patient-pdf', 'submit-patient-sections', 'view-patient-sections',
            
            // Medical data
            'view-questions', 'create-questions', 'edit-questions',
            'view-sections', 'view-scores', 'view-score-history',
            'view-recommendations', 'create-recommendations', 'edit-recommendations', 'delete-recommendations',
            
            // Consultations (full)
            'view-consultations', 'create-consultations', 'view-consultation-details',
            'edit-consultations', 'add-consultation-doctors', 'remove-consultation-doctors',
            'toggle-consultation-status', 'view-consultation-members', 'reply-consultations',
            'search-consultation-doctors',
            
            // AI
            'use-ai-consultation', 'view-ai-history',
            
            // Content
            'view-posts', 'create-posts', 'edit-posts', 'delete-posts', 'like-posts', 'save-posts',
            'view-trending-posts', 'search-posts', 'search-hashtags',
            'view-comments', 'create-comments', 'edit-comments', 'delete-comments', 'like-comments',
            
            // Groups
            'view-groups', 'view-group-details', 'create-groups', 'edit-groups', 'delete-groups',
            'join-groups', 'leave-groups', 'view-my-groups', 'invite-group-members',
            'remove-group-members', 'handle-group-invitations', 'view-group-members',
            
            // Communication
            'view-notifications', 'view-new-notifications', 'mark-notification-read', 
            'mark-all-notifications-read', 'manage-fcm-tokens',
            
            // Profile
            'upload-profile-image', 'change-user-password', 'manage-user-locale',
            
            // Media
            'upload-images', 'upload-videos', 'upload-files',
            
            // Achievements
            'view-achievements', 'assign-achievements',
            
            // Doses
            'view-doses', 'create-doses', 'edit-doses', 'search-doses',
        ]);
        $this->command->info('Senior Doctor role created!');

        // Doctor (Standard) Role
        $doctor = Role::firstOrCreate(['name' => 'doctor']);
        $doctor->givePermissionTo([
            // Patient management (own + view all)
            'view-patients', 'view-patient-details', 'create-patients', 'edit-patients',
            'search-patients', 'view-current-patients', 'view-all-patients',
            'mark-patients', 'view-marked-patients', 'upload-patient-files', 'filter-patients',
            'generate-patient-pdf', 'submit-patient-sections', 'view-patient-sections',
            
            // Medical data (view + limited edit)
            'view-questions', 'view-sections', 'view-scores', 'view-score-history',
            'view-recommendations', 'create-recommendations', 'edit-recommendations',
            
            // Patient comments
            'view-patient-comments', 'create-patient-comments', 'edit-patient-comments', 'delete-patient-comments',
            
            // Consultations
            'view-consultations', 'create-consultations', 'view-consultation-details',
            'reply-consultations', 'search-consultation-doctors',
            
            // AI
            'use-ai-consultation', 'view-ai-history',
            
            // Content
            'view-posts', 'create-posts', 'edit-posts', 'delete-posts', 'like-posts', 'save-posts',
            'view-trending-posts', 'search-posts', 'search-hashtags',
            'view-comments', 'create-comments', 'edit-comments', 'delete-comments', 'like-comments',
            
            // Groups
            'view-groups', 'view-group-details', 'create-groups', 'edit-groups', 'delete-groups',
            'join-groups', 'leave-groups', 'view-my-groups', 'invite-group-members',
            'handle-group-invitations', 'view-group-members',
            
            // Polls
            'view-polls', 'create-polls', 'vote-polls', 'view-poll-voters',
            
            // Communication
            'view-notifications', 'view-new-notifications', 'mark-notification-read', 
            'mark-all-notifications-read', 'manage-fcm-tokens',
            'create-contacts',
            
            // Profile
            'view-user-profile', 'upload-profile-image', 'upload-syndicate-card',
            'change-user-password', 'manage-user-locale',
            
            // Media
            'upload-images', 'upload-videos', 'upload-files',
            
            // Achievements
            'view-achievements', 'view-user-achievements',
            
            // Doses
            'view-doses', 'search-doses',
            
            // Sharing
            'generate-share-urls', 'view-share-preview',
        ]);
        $this->command->info('Doctor role created!');

        // Junior Doctor Role
        $juniorDoctor = Role::firstOrCreate(['name' => 'junior-doctor']);
        $juniorDoctor->givePermissionTo([
            // Patient management (limited)
            'view-patients', 'view-patient-details', 'create-patients',
            'search-patients', 'view-current-patients',
            'mark-patients', 'view-marked-patients', 'upload-patient-files',
            'generate-patient-pdf', 'view-patient-sections',
            
            // Medical data (view only)
            'view-questions', 'view-sections', 'view-scores', 'view-recommendations',
            
            // Patient comments
            'view-patient-comments', 'create-patient-comments',
            
            // Consultations (view only)
            'view-consultations', 'view-consultation-details',
            
            // Content
            'view-posts', 'create-posts', 'edit-posts', 'delete-posts', 'like-posts', 'save-posts',
            'view-comments', 'create-comments', 'edit-comments', 'delete-comments',
            
            // Groups
            'view-groups', 'view-group-details', 'join-groups', 'leave-groups',
            'view-my-groups', 'handle-group-invitations',
            
            // Communication
            'view-notifications', 'mark-notification-read', 'manage-fcm-tokens',
            
            // Profile
            'upload-profile-image', 'change-user-password', 'manage-user-locale',
            
            // Media
            'upload-images',
            
            // Doses
            'view-doses', 'search-doses',
        ]);
        $this->command->info('Junior Doctor role created!');

        // Moderator Role
        $moderator = Role::firstOrCreate(['name' => 'moderator']);
        $moderator->givePermissionTo([
            // Content moderation
            'view-posts', 'edit-any-post', 'delete-any-post', 'moderate-posts',
            'view-comments', 'delete-any-comment', 'moderate-comments',
            'view-groups', 'view-group-details', 'delete-any-group',
            
            // User management
            'view-users', 'view-user-profile', 'block-users',
            
            // Communication
            'send-push-notifications', 'create-notifications',
            'view-contacts', 'edit-contacts', 'delete-contacts',
            
            // Reports
            'view-reports',
        ]);
        $this->command->info('Moderator role created!');

        // Content Manager Role
        $contentManager = Role::firstOrCreate(['name' => 'content-manager']);
        $contentManager->givePermissionTo([
            // Content
            'view-posts', 'create-posts', 'edit-posts', 'delete-posts',
            'view-comments', 'create-comments', 'edit-comments', 'delete-comments',
            'view-trending-posts', 'search-posts',
            
            // Groups
            'view-groups', 'create-groups', 'edit-groups', 'delete-groups',
            'invite-group-members', 'view-group-members',
            
            // Communication
            'send-push-notifications', 'create-notifications',
            
            // Media
            'upload-images', 'upload-videos',
        ]);
        $this->command->info('Content Manager role created!');

        // Viewer Role
        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->givePermissionTo([
            // View only
            'view-posts', 'view-trending-posts', 'search-posts',
            'view-comments',
            'view-groups', 'view-group-details',
            'view-notifications', 'mark-notification-read',
            'view-doses', 'search-doses',
        ]);
        $this->command->info('Viewer role created!');

        $this->command->info('✅ All roles and permissions seeded successfully!');
    }
}
```

---

## 🔍 Quick Reference

### Total Permissions: **~180 permissions**

### Permission Categories:
1. **users** - 14 permissions
2. **patients** - 16 permissions
3. **medical** - 16 permissions
4. **posts** - 15 permissions
5. **comments** - 11 permissions
6. **groups** - 16 permissions
7. **consultations** - 10 permissions
8. **ai** - 2 permissions
9. **communication** - 13 permissions
10. **polls** - 5 permissions
11. **doses** - 5 permissions
12. **achievements** - 7 permissions
13. **reports** - 5 permissions
14. **settings** - 4 permissions
15. **roles** - 11 permissions
16. **media** - 4 permissions
17. **sharing** - 3 permissions
18. **admin** - 5 permissions

---

## 📞 Support

For questions or issues:
- Check the Filament admin panel: `/admin/permissions`
- View role assignments: `/admin/roles`
- Test permissions: `POST /api/v2/checkPermission`

---

**Last Updated**: October 8, 2025
**Version**: 2.0
**Author**: EGYAKIN Development Team

