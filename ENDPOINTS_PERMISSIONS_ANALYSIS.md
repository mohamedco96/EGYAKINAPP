# Endpoints & Permissions Analysis

## 📋 Summary

Based on Flutter frontend endpoints analysis, this document lists:
1. **Endpoints that DO NOT need permissions** (public/auth endpoints)
2. **Endpoints that need permissions** (organized by category)
3. **New permissions created** based on actual frontend usage

---

## 🚫 Endpoints That DO NOT Need Permissions

These endpoints are **public** or **authentication-related** and should be accessible without permission checks:

### Authentication Endpoints
- ✅ `POST /api/v2/login` - User login
- ✅ `POST /api/v2/register` - User registration
- ✅ `POST /api/v2/logout` - User logout
- ✅ `POST /api/v2/auth/social/google` - Google sign-in

### Password Reset Flow
- ✅ `POST /api/v2/forgotpassword` - Request password reset
- ✅ `POST /api/v2/resetpasswordverification` - Verify OTP for password reset
- ✅ `POST /api/v2/resetpassword` - Reset password

### Email Verification
- ✅ `POST /api/v2/sendverificationmail` - Send verification email
- ✅ `POST /api/v2/emailverification` - Verify email with OTP

### Public Endpoints
- ✅ `GET /api/v2/settings` - App settings (public information)
- ✅ `POST /api/v2/contact` - Contact us form (public)

### User Actions (No Permission Required)
- ✅ `POST /api/v2/user/locale` - Change user language
- ✅ `GET /api/v2/shownotification` - View notifications
- ✅ `PUT /api/v2/notification` - Mark notifications as read
- ✅ `POST /api/v2/storeFCM` - Store FCM token

**Total: 15 endpoints that don't need permissions**

---

## ✅ Endpoints That Need Permissions

### Home & Dashboard (1 permission)
- `GET /api/v2/homeNew` → `access-home`

### Patient Management (18 permissions)
- `GET /api/v2/allPatientsNew` → `view-all-patients`
- `GET /api/v2/currentPatientsNew` → `view-current-patients`
- `POST /api/v2/searchNew` → `search-patients`
- `GET /api/v2/showSections/{patientId}` → `view-patient-sections`
- `GET /api/v2/patient/{sectionId}/{patientId}` → `view-patient-details`
- `POST /api/v2/patient` → `create-patient`
- `PUT /api/v2/patientsection/{sectionId}/{patientId}` → `update-patient-section`
- `DELETE /api/v2/patient/{patientId}` → `delete-patient`
- `GET /api/v2/questions/{sectionId}` → `get-patient-questions`
- `PUT /api/v2/patient/{sectionId}/{patientId}` (outcome) → `submit-patient-outcome`
- `PUT /api/v2/submitStatus/{patientId}` → `final-submit-patient`
- `GET /api/v2/generatePDF/{patientId}` → `generate-patient-pdf`
- `POST /api/v2/markedPatients/{patientId}` → `mark-patient`
- `POST /api/v2/markedPatients/{patientId}` (unmark) → `unmark-patient`
- `POST /api/v2/patientFilters` → `apply-patient-filters`
- `GET /api/v2/patientFilters` → `get-patient-filters`
- `POST /api/v2/exportFilteredPatients` → `export-filtered-patients`

### Patient Comments (3 permissions)
- `GET /api/v2/comment/{patientId}` → `view-patient-comments`
- `POST /api/v2/comment` → `create-patient-comment`
- `DELETE /api/v2/comment/{commentId}` → `delete-patient-comment`

### Recommendations (4 permissions)
- `GET /api/v2/recommendations/{patientId}` → `view-recommendations`
- `POST /api/v2/recommendations/{patientId}` → `create-recommendation`
- `PUT /api/v2/recommendations/{patientId}` → `update-recommendation`
- `DELETE /api/v2/recommendations/{patientId}` → `delete-recommendation`

### Doses/Medications (2 permissions)
- `GET /api/v2/dose/search/{dose}` → `search-doses`
- `POST /api/v2/dose` → `create-dose`

### User Profile (9 permissions)
- `PUT /api/v2/users` → `update-profile`
- `POST /api/v2/upload-profile-image` → `upload-profile-image`
- `POST /api/v2/uploadSyndicateCard` → `upload-syndicate-card`
- `POST /api/v2/changePassword` → `change-password`
- `GET /api/v2/showAnotherProfile/{doctorId}` → `view-doctor-profile`
- `GET /api/v2/doctorProfileGetPatients/{doctorId}` → `view-doctor-patients`
- `GET /api/v2/doctorProfileGetScoreHistory/{doctorId}` → `view-doctor-score-history`
- `GET /api/v2/users/{doctorId}/achievements` → `view-doctor-achievements`

### Admin User Management (3 permissions - Admin only)
- `PUT /api/v2/users/{doctorId}` (syndicate card) → `verify-syndicate-card`
- `PUT /api/v2/users/{doctorId}` (block) → `block-user`
- `PUT /api/v2/users/{doctorId}` (verify email) → `verify-user-email`

### File Uploads (1 permission)
- `POST /api/v2/uploadFileNew` → `upload-patient-files`

### Consultations (10 permissions)
- `POST /api/v2/consultationDoctorSearch/{searchContent}` → `search-consultation-doctors`
- `POST /api/v2/consultations` → `create-consultation`
- `GET /api/v2/consultations/sent` → `view-sent-consultations`
- `GET /api/v2/consultations/received` → `view-received-consultations`
- `GET /api/v2/consultations/{consultationId}` → `view-consultation-details`
- `PUT /api/v2/consultations/{consultationId}` → `reply-consultation`
- `GET /api/v2/consultations/{consultationId}/members` → `view-consultation-members`
- `PUT /api/v2/consultations/{consultationId}/toggle-status` → `toggle-consultation-status`
- `DELETE /api/v2/consultations/{consultationId}/doctors/{doctorId}` → `remove-consultation-member`
- `POST /api/v2/consultations/{consultationId}/add-doctors` → `add-consultation-doctors`

### AI Consultations (2 permissions)
- `GET /api/v2/AIconsultation-history/{patientId}` → `view-ai-consultation-history`
- `POST /api/v2/AIconsultation/{patientId}` → `send-ai-consultation`

### Feed Posts (11 permissions)
- `GET /api/v2/feed/posts` → `view-feed-posts`
- `POST /api/v2/feed/posts` → `create-feed-post`
- `POST /api/v2/feed/posts/{postId}` → `edit-feed-post`
- `DELETE /api/v2/feed/posts/{postId}` → `delete-feed-post`
- `POST /api/v2/feed/posts/{postId}/likeOrUnlikePost` → `like-feed-post`
- `POST /api/v2/feed/posts/{postId}/saveOrUnsavePost` → `save-feed-post`
- `GET /api/v2/feed/posts/{postId}` → `view-feed-post`
- `GET /api/v2/feed/trendingPosts` → `view-trending-posts`
- `POST /api/v2/feed/searchPosts` → `search-feed-posts`
- `GET /api/v2/doctorposts/{doctorId}` → `view-doctor-posts`
- `GET /api/v2/doctorsavedposts/{doctorId}` → `view-saved-posts`

### Feed Comments (5 permissions)
- `GET /api/v2/posts/{postId}/comments` → `view-feed-comments`
- `POST /api/v2/feed/posts/{postId}/comment` → `create-feed-comment`
- `DELETE /api/v2/feed/comments/{commentId}` → `delete-feed-comment`
- `POST /api/v2/comments/{commentId}/likeOrUnlikeComment` → `like-feed-comment`
- `POST /api/v2/feed/posts/{postId}/comment` (reply) → `reply-feed-comment`

### Legacy Posts (4 permissions)
- `GET /api/v2/post` → `view-legacy-posts`
- `GET /api/v2/Postcomments/{postId}` → `view-legacy-post-comments`
- `POST /api/v2/Postcomments` → `create-legacy-post-comment`
- `DELETE /api/v2/Postcomments/{commentId}` → `delete-legacy-post-comment`

### Groups (14 permissions)
- `GET /api/v2/groups` → `view-groups`
- `GET /api/v2/latest-groups-with-random-posts` → `view-groups-tab`
- `GET /api/v2/groups/{groupId}/detailsWithPosts` → `view-group-details`
- `POST /api/v2/groups` → `create-group`
- `POST /api/v2/groups/{groupId}` → `update-group`
- `DELETE /api/v2/groups/{groupId}` → `delete-group`
- `POST /api/v2/groups/{groupId}/join` → `join-group`
- `POST /api/v2/groups/{groupId}/leave` → `leave-group`
- `GET /api/v2/groups/{groupId}/members` → `view-group-members`
- `GET /api/v2/mygroups` → `view-my-groups`
- `POST /api/v2/groups/{groupId}/invite` → `send-group-invitation`
- `POST /api/v2/groups/{groupId}/removeMember` → `remove-group-member`
- `GET /api/v2/groups/invitations/{doctorId}` → `view-group-invitations`
- `POST /api/v2/groups/{groupId}/invitation` → `handle-group-invitation`

### Polls (3 permissions)
- `POST /api/v2/polls/{pollId}/vote` → `vote-poll`
- `POST /api/v2/polls/{pollId}/options` → `add-poll-option`
- `GET /api/v2/polls/{pollId}/options/{optionId}/voters` → `view-poll-voters`

### Post Likes (1 permission)
- `GET /api/v2/posts/{postId}/likes` → `view-post-likes`

---

## 📊 Statistics

- **Total Endpoints Analyzed:** ~100+
- **Endpoints Without Permissions:** 15 (public/auth/user actions)
- **Endpoints With Permissions:** ~85
- **Total Permissions Created:** 91

---

## 🎯 Permission Naming Convention

All permissions follow the pattern: `{action}-{resource}`

**Examples:**
- `view-all-patients` - View all patients
- `create-patient` - Create new patient
- `delete-feed-post` - Delete feed post
- `toggle-consultation-status` - Lock/unlock consultation

---

## 🔄 Migration Notes

1. **Old permissions are dropped** - All existing permissions are deleted
2. **New permissions are created** - Based on actual Flutter endpoints
3. **Roles are preserved** - Existing roles remain, permissions are reassigned
4. **Run seeder:** `php artisan db:seed --class=RolePermissionSeeder`

---

## ⚠️ Important Notes

1. **Public endpoints** listed above should NOT have permission middleware
2. **Admin-only endpoints** (verify-syndicate-card, block-user, verify-user-email) should only be assigned to admin roles
3. **Permission names** match the endpoint functionality for easy mapping
4. **Categories** are used for organization but permissions are checked by name

