# Frontend Roles & Permissions Guide

## Overview

The application uses a **Role-Based Access Control (RBAC)** system where:
- Each user has **exactly one role**
- Permissions are assigned to roles (not directly to users)
- Users inherit all permissions from their assigned role
- The frontend receives role and permissions information on login and when permissions change

## Key Concepts

### Roles
Available roles in the system:
- **super-admin**: Full access to all permissions
- **admin**: Administrative access (most permissions)
- **doctor**: Standard medical professional permissions
- **junior-doctor**: Limited doctor permissions
- **viewer**: Read-only access

### Permissions
- Each permission is linked to a specific API endpoint
- Permissions are organized into categories (e.g., patients, feed, consultations)
- Permissions are checked on the backend before allowing API access

### Permission Change Tracking
- When a user's role or permissions change, a flag `permissions_changed` is set to `true`
- The frontend should check this flag and refetch permissions when needed

---

## API Endpoints

### 1. Login Endpoint
**POST** `/api/v2/login`

**Response:**
```json
{
  "value": true,
  "message": "User logged in successfully",
  "token": "sanctum_token_here",
  "data": { /* user data */ },
  "role": "doctor",                    // Single role name (string, not array)
  "permissions": [                     // Array of permission names
    "access-home",
    "view-all-patients",
    "create-patient",
    // ... all permissions for this role
  ],
  "status_code": 200
}
```

**Important:**
- `role` is a **single string** (not an array)
- `permissions` is an **array of permission name strings**
- Store both `role` and `permissions` in your app state/local storage

---

### 2. Home Data Endpoint
**GET** `/api/v2/homeNew`

**Response includes:**
```json
{
  "value": true,
  "role": "doctor",
  "permissions_changed": false,       // NEW: Boolean flag
  // ... other home data
}
```

**Flow:**
1. Check `permissions_changed` flag on app startup or when navigating to home
2. If `permissions_changed === true`, call the role-permissions endpoint to get updated permissions
3. Update your app's stored role and permissions
4. Refresh UI elements based on new permissions

---

### 3. Get Role & Permissions Endpoint
**GET** `/api/v2/user/role-permissions`

**Authentication:** Required (Bearer token)

**Response:**
```json
{
  "value": true,
  "message": "Role and permissions retrieved successfully",
  "role": "doctor",
  "permissions": [
    "access-home",
    "view-all-patients",
    // ... updated permissions list
  ]
}
```

**Important:**
- This endpoint **automatically resets** `permissions_changed` to `false` after fetching
- Call this endpoint whenever `permissions_changed === true` from the home endpoint
- Update your app state with the new `role` and `permissions`

---

## Frontend Implementation Flow

### On App Startup / Login
```
1. User logs in
   ↓
2. Receive response with: role, permissions, token
   ↓
3. Store role and permissions in app state/local storage
   ↓
4. Navigate to home screen
```

### On Home Screen Load
```
1. Call GET /api/v2/homeNew
   ↓
2. Check permissions_changed flag
   ↓
3. If permissions_changed === true:
   - Call GET /api/v2/user/role-permissions
   - Update stored role and permissions
   - Refresh UI based on new permissions
   ↓
4. If permissions_changed === false:
   - Continue with existing permissions
```

### Permission Checking in Frontend
```dart
// Example Flutter/Dart implementation
bool hasPermission(String permissionName) {
  return storedPermissions.contains(permissionName);
}

// Check before showing UI elements
if (hasPermission('create-patient')) {
  // Show "Add Patient" button
}

// Check before making API calls
if (hasPermission('delete-patient')) {
  // Allow delete action
} else {
  // Show error or hide delete button
}
```

---

## Complete Permissions Table

| Permission Name | Category | Description | API Endpoint |
|----------------|----------|-------------|--------------|
| **Home & Dashboard** |
| `access-home` | home | Access home dashboard | `GET /api/v2/homeNew` |
| **Patient Management** |
| `view-all-patients` | patients | View all patients | `GET /api/v2/allPatientsNew` |
| `view-current-patients` | patients | View current/assigned patients | `GET /api/v2/currentPatientsNew` |
| `search-patients` | patients | Search patients | `POST /api/v2/searchNew` |
| `view-patient-sections` | patients | View patient sections | `GET /api/v2/showSections/{patientId}` |
| `view-patient-details` | patients | View patient section details | `GET /api/v2/patient/{sectionId}/{patientId}` |
| `create-patient` | patients | Create new patient | `POST /api/v2/patient` |
| `update-patient-section` | patients | Update patient section | `PUT /api/v2/patientsection/{sectionId}/{patientId}` |
| `delete-patient` | patients | Delete patient | `DELETE /api/v2/patient/{patientId}` |
| `get-patient-questions` | patients | Get patient questions | `GET /api/v2/questions/{sectionId}` |
| `submit-patient-outcome` | patients | Submit patient outcome | `PUT /api/v2/patient/{sectionId}/{patientId}` |
| `final-submit-patient` | patients | Final submit patient | `PUT /api/v2/submitStatus/{patientId}` |
| `generate-patient-pdf` | patients | Generate patient PDF report | `GET /api/v2/generatePDF/{patientId}` |
| `mark-patient` | patients | Mark/bookmark patient | `POST /api/v2/markedPatients/{patientId}` |
| `unmark-patient` | patients | Unmark patient | `POST /api/v2/markedPatients/{patientId}` |
| `apply-patient-filters` | patients | Apply patient filters | `POST /api/v2/patientFilters` |
| `get-patient-filters` | patients | Get patient filter options | `GET /api/v2/patientFilters` |
| `export-filtered-patients` | patients | Export filtered patients | `POST /api/v2/exportFilteredPatients` |
| **Patient Comments** |
| `view-patient-comments` | patient-comments | View patient comments | `GET /api/v2/comment/{patientId}` |
| `create-patient-comment` | patient-comments | Create patient comment | `POST /api/v2/comment` |
| `delete-patient-comment` | patient-comments | Delete patient comment | `DELETE /api/v2/comment/{commentId}` |
| **Recommendations** |
| `view-recommendations` | recommendations | View patient recommendations | `GET /api/v2/recommendations/{patientId}` |
| `create-recommendation` | recommendations | Create patient recommendation | `POST /api/v2/recommendations/{patientId}` |
| `update-recommendation` | recommendations | Update patient recommendation | `PUT /api/v2/recommendations/{patientId}` |
| `delete-recommendation` | recommendations | Delete patient recommendation | `DELETE /api/v2/recommendations/{patientId}` |
| **Doses/Medications** |
| `search-doses` | doses | Search for doses/medications | `GET /api/v2/dose/search/{dose}` |
| `create-dose` | doses | Create new medicine/dose | `POST /api/v2/dose` |
| **User Profile** |
| `update-profile` | profile | Update user profile | `PUT /api/v2/users` |
| `upload-profile-image` | profile | Upload profile image | `POST /api/v2/upload-profile-image` |
| `upload-syndicate-card` | profile | Upload syndicate card | `POST /api/v2/uploadSyndicateCard` |
| `change-password` | profile | Change password | `POST /api/v2/changePassword` |
| `view-doctor-profile` | profile | View doctor profile | `GET /api/v2/showAnotherProfile/{doctorId}` |
| `view-doctor-patients` | profile | View doctor patients | `GET /api/v2/doctorProfileGetPatients/{doctorId}` |
| `view-doctor-score-history` | profile | View doctor score history | `GET /api/v2/doctorProfileGetScoreHistory/{doctorId}` |
| `view-doctor-achievements` | profile | View doctor achievements | `GET /api/v2/users/{doctorId}/achievements` |
| **Admin Management** |
| `verify-syndicate-card` | admin | Verify syndicate card (Admin) | `PUT /api/v2/users/{doctorId}` |
| `block-user` | admin | Block/unblock user (Admin) | `PUT /api/v2/users/{doctorId}` |
| `verify-user-email` | admin | Verify user email (Admin) | `PUT /api/v2/users/{doctorId}` |
| **File Uploads** |
| `upload-patient-files` | files | Upload patient files | `POST /api/v2/uploadFileNew` |
| **Consultations** |
| `search-consultation-doctors` | consultations | Search doctors for consultation | `POST /api/v2/consultationDoctorSearch/{searchContent}` |
| `create-consultation` | consultations | Create consultation | `POST /api/v2/consultations` |
| `view-sent-consultations` | consultations | View sent consultations | `GET /api/v2/consultations/sent` |
| `view-received-consultations` | consultations | View received consultations | `GET /api/v2/consultations/received` |
| `view-consultation-details` | consultations | View consultation details | `GET /api/v2/consultations/{consultationId}` |
| `reply-consultation` | consultations | Reply to consultation | `PUT /api/v2/consultations/{consultationId}` |
| `view-consultation-members` | consultations | View consultation members | `GET /api/v2/consultations/{consultationId}/members` |
| `toggle-consultation-status` | consultations | Lock/unlock consultation | `PUT /api/v2/consultations/{consultationId}/toggle-status` |
| `remove-consultation-member` | consultations | Remove member from consultation | `DELETE /api/v2/consultations/{consultationId}/doctors/{doctorId}` |
| `add-consultation-doctors` | consultations | Add doctors to consultation | `POST /api/v2/consultations/{consultationId}/add-doctors` |
| **AI Consultations** |
| `view-ai-consultation-history` | ai | View AI consultation history | `GET /api/v2/AIconsultation-history/{patientId}` |
| `send-ai-consultation` | ai | Send AI consultation request | `POST /api/v2/AIconsultation/{patientId}` |
| **Feed Posts** |
| `view-feed-posts` | feed | View feed posts | `GET /api/v2/feed/posts` |
| `create-feed-post` | feed | Create feed post | `POST /api/v2/feed/posts` |
| `edit-feed-post` | feed | Edit feed post | `POST /api/v2/feed/posts/{postId}` |
| `delete-feed-post` | feed | Delete feed post | `DELETE /api/v2/feed/posts/{postId}` |
| `like-feed-post` | feed | Like/unlike feed post | `POST /api/v2/feed/posts/{postId}/likeOrUnlikePost` |
| `save-feed-post` | feed | Save/unsave feed post | `POST /api/v2/feed/posts/{postId}/saveOrUnsavePost` |
| `view-feed-post` | feed | View single feed post | `GET /api/v2/feed/posts/{postId}` |
| `view-trending-posts` | feed | View trending posts | `GET /api/v2/feed/trendingPosts` |
| `search-feed-posts` | feed | Search feed posts | `POST /api/v2/feed/searchPosts` |
| `view-doctor-posts` | feed | View doctor posts | `GET /api/v2/doctorposts/{doctorId}` |
| `view-saved-posts` | feed | View saved posts | `GET /api/v2/doctorsavedposts/{doctorId}` |
| `view-post-likes` | feed | View post likes | `GET /api/v2/posts/{postId}/likes` |
| **Feed Comments** |
| `view-feed-comments` | feed-comments | View feed post comments | `GET /api/v2/posts/{postId}/comments` |
| `create-feed-comment` | feed-comments | Create feed comment | `POST /api/v2/feed/posts/{postId}/comment` |
| `delete-feed-comment` | feed-comments | Delete feed comment | `DELETE /api/v2/feed/comments/{commentId}` |
| `like-feed-comment` | feed-comments | Like/unlike feed comment | `POST /api/v2/comments/{commentId}/likeOrUnlikeComment` |
| `reply-feed-comment` | feed-comments | Reply to feed comment | `POST /api/v2/feed/posts/{postId}/comment` |
| **Legacy Posts** |
| `view-legacy-posts` | legacy-posts | View legacy posts | `GET /api/v2/post` |
| `view-legacy-post-comments` | legacy-posts | View legacy post comments | `GET /api/v2/Postcomments/{postId}` |
| `create-legacy-post-comment` | legacy-posts | Create legacy post comment | `POST /api/v2/Postcomments` |
| `delete-legacy-post-comment` | legacy-posts | Delete legacy post comment | `DELETE /api/v2/Postcomments/{commentId}` |
| **Groups** |
| `view-groups` | groups | View all groups | `GET /api/v2/groups` |
| `view-groups-tab` | groups | View groups tab | `GET /api/v2/latest-groups-with-random-posts` |
| `view-group-details` | groups | View group details | `GET /api/v2/groups/{groupId}/detailsWithPosts` |
| `create-group` | groups | Create group | `POST /api/v2/groups` |
| `update-group` | groups | Update group | `POST /api/v2/groups/{groupId}` |
| `delete-group` | groups | Delete group | `DELETE /api/v2/groups/{groupId}` |
| `join-group` | groups | Join group | `POST /api/v2/groups/{groupId}/join` |
| `leave-group` | groups | Leave group | `POST /api/v2/groups/{groupId}/leave` |
| `view-group-members` | groups | View group members | `GET /api/v2/groups/{groupId}/members` |
| `view-my-groups` | groups | View my groups | `GET /api/v2/mygroups` |
| `send-group-invitation` | groups | Send group invitation | `POST /api/v2/groups/{groupId}/invite` |
| `remove-group-member` | groups | Remove group member | `POST /api/v2/groups/{groupId}/removeMember` |
| `view-group-invitations` | groups | View group invitations | `GET /api/v2/groups/invitations/{doctorId}` |
| `handle-group-invitation` | groups | Accept/decline group invitation | `POST /api/v2/groups/{groupId}/invitation` |
| **Polls** |
| `vote-poll` | polls | Vote on poll | `POST /api/v2/polls/{pollId}/vote` |
| `add-poll-option` | polls | Add poll option | `POST /api/v2/polls/{pollId}/options` |
| `view-poll-voters` | polls | View poll voters | `GET /api/v2/polls/{pollId}/options/{optionId}/voters` |

---

## Endpoints That Do NOT Require Permissions

The following endpoints are **public** and do not require any permission checks:

1. `POST /api/v2/login` - User login
2. `POST /api/v2/register` - User registration
3. `POST /api/v2/logout` - User logout
4. `POST /api/v2/forgotpassword` - Forgot password
5. `POST /api/v2/resetpasswordverification` - Reset password verification
6. `POST /api/v2/resetpassword` - Reset password
7. `POST /api/v2/sendverificationmail` - Send verification email
8. `POST /api/v2/emailverification` - Email verification
9. `POST /api/v2/auth/social/google` - Google social authentication
10. `GET /api/v2/settings` - App settings (public)
11. `POST /api/v2/contact` - Contact us (public)
12. `POST /api/v2/user/locale` - Change user language (no permission required)
13. `GET /api/v2/shownotification` - View notifications (no permission required)
14. `PUT /api/v2/notification` - Mark notifications as read (no permission required)
15. `POST /api/v2/storeFCM` - Store FCM token (no permission required)

---

## Best Practices

### 1. Store Permissions
- Store `role` and `permissions` array in your app state/local storage after login
- Keep them synchronized with the backend

### 2. Check Permissions Before Actions
- Always check if user has permission before showing UI elements (buttons, menus)
- Check permissions before making API calls that require them
- Show appropriate error messages if user lacks permission

### 3. Handle Permission Changes
- Always check `permissions_changed` flag on home screen load
- When `true`, immediately fetch updated permissions
- Update UI to reflect new permissions (show/hide features)

### 4. Error Handling
- If API returns 403 (Forbidden), user likely lacks required permission
- Refresh permissions and update UI accordingly
- Show user-friendly error messages

### 5. Permission Names
- Use exact permission names as shown in the table above
- Permission names are case-sensitive
- Always use lowercase with hyphens (e.g., `create-patient`, not `CreatePatient`)

---

## Example Implementation (Flutter/Dart)

```dart
class PermissionService {
  static List<String>? _permissions;
  static String? _role;
  
  // Store permissions after login
  static void storePermissions(String role, List<String> permissions) {
    _role = role;
    _permissions = permissions;
    // Also save to SharedPreferences or your state management
  }
  
  // Check if user has permission
  static bool hasPermission(String permissionName) {
    return _permissions?.contains(permissionName) ?? false;
  }
  
  // Check if user has any of the given permissions
  static bool hasAnyPermission(List<String> permissionNames) {
    return permissionNames.any((perm) => hasPermission(perm));
  }
  
  // Get user role
  static String? getRole() => _role;
  
  // Update permissions (when permissions_changed is true)
  static Future<void> refreshPermissions() async {
    final response = await api.get('/api/v2/user/role-permissions');
    if (response['value'] == true) {
      storePermissions(
        response['role'],
        List<String>.from(response['permissions'])
      );
    }
  }
}

// Usage in widgets
if (PermissionService.hasPermission('create-patient')) {
  ElevatedButton(
    onPressed: () => createPatient(),
    child: Text('Add Patient'),
  )
}

// Check on home screen
void checkPermissionsChanged() async {
  final homeData = await api.get('/api/v2/homeNew');
  if (homeData['permissions_changed'] == true) {
    await PermissionService.refreshPermissions();
    // Refresh UI
    setState(() {});
  }
}
```

---

## Summary

1. **Single Role**: Each user has exactly one role
2. **Role-Based Permissions**: Permissions come from the user's role
3. **Permission Tracking**: Check `permissions_changed` flag to know when to refresh
4. **API Endpoints**: Use the table above to map permissions to endpoints
5. **Frontend Checks**: Always verify permissions before showing features or making API calls

For questions or issues, contact the backend team.

