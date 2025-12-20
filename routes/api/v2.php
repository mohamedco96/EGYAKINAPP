<?php

use App\Http\Controllers\Api\V2\AchievementController;
use App\Http\Controllers\Api\V2\AuthController;
use App\Http\Controllers\Api\V2\ChatController;
use App\Http\Controllers\Api\V2\CommentController;
use App\Http\Controllers\Api\V2\ConsultationController;
use App\Http\Controllers\Api\V2\ContactController;
use App\Http\Controllers\Api\V2\DoseController;
use App\Http\Controllers\Api\V2\EmailVerificationController;
use App\Http\Controllers\Api\V2\FeedPostController;
use App\Http\Controllers\Api\V2\ForgetPasswordController;
use App\Http\Controllers\Api\V2\GroupController;
use App\Http\Controllers\Api\V2\LocalizationTestController;
use App\Http\Controllers\Api\V2\LocalizedNotificationController;
use App\Http\Controllers\Api\V2\MainController;
use App\Http\Controllers\Api\V2\NotificationController;
use App\Http\Controllers\Api\V2\OtpController;
use App\Http\Controllers\Api\V2\PatientsController;
use App\Http\Controllers\Api\V2\PollController;
use App\Http\Controllers\Api\V2\PostCommentsController;
use App\Http\Controllers\Api\V2\PostsController;
use App\Http\Controllers\Api\V2\QuestionsController;
use App\Http\Controllers\Api\V2\RecommendationController;
use App\Http\Controllers\Api\V2\ResetPasswordController;
use App\Http\Controllers\Api\V2\RolePermissionController;
use App\Http\Controllers\Api\V2\SectionsController;
use App\Http\Controllers\Api\V2\SettingsController;
use App\Http\Controllers\Api\V2\ShareController;
use App\Http\Controllers\Api\V2\UserLocaleController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 Routes
|--------------------------------------------------------------------------
|
| This file contains all the API routes for version 2 of the application.
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned the "api" middleware group and "v2" prefix.
|
| All new changes and features should be added to this version.
|
*/

// Public routes for V2
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Localization test routes (for development and testing)
Route::get('/localization/test', [LocalizationTestController::class, 'test']);
Route::post('/localization/login', [LocalizationTestController::class, 'login']);
Route::post('/localization/patient', [LocalizationTestController::class, 'createPatient']);
Route::post('/localization/points', [LocalizationTestController::class, 'awardPoints']);
Route::post('/forgotpassword', [ForgetPasswordController::class, 'forgotPassword']);
Route::post('/resetpasswordverification', [ResetPasswordController::class, 'resetpasswordverification']);
Route::post('/resetpassword', [ResetPasswordController::class, 'resetpassword']);
Route::get('/generatePDF/{patient_id}', [PatientsController::class, 'generatePatientPDF']);
Route::post('/send-notification', [AuthController::class, 'sendPushNotificationTest']);
Route::post('/sendAllPushNotification', [NotificationController::class, 'sendAllPushNotification']);

// Email verification routes
Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail']);
Route::post('/email/verify', [EmailVerificationController::class, 'verifyEmail']);

// Settings routes (public)
Route::get('/settings', [SettingsController::class, 'index']);
Route::post('/settings', [SettingsController::class, 'store']);
Route::get('/settings/{settings}', [SettingsController::class, 'show']);
Route::put('/settings/{settings}', [SettingsController::class, 'update']);
Route::delete('/settings/{settings}', [SettingsController::class, 'destroy']);

// Social Authentication Routes
Route::prefix('auth/social')->group(function () {
    // Web-based OAuth flows (for web applications) - requires session for OAuth state
    Route::middleware(['web'])->group(function () {
        Route::get('/google', [SocialAuthController::class, 'redirectToGoogle']);
        Route::get('/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
        Route::get('/apple', [SocialAuthController::class, 'redirectToApple']);
        Route::match(['get', 'post'], '/apple/callback', [SocialAuthController::class, 'handleAppleCallback']);
    });

    // API-based authentication (for mobile applications)
    Route::post('/google', [SocialAuthController::class, 'googleAuth']);
    Route::post('/apple', [SocialAuthController::class, 'appleAuth']);
});

// Protected routes (require auth:sanctum middleware)
Route::group(['middleware' => ['auth:sanctum', 'check.blocked.home']], function () {

    // General upload routes
    Route::post('/uploadImage', [MainController::class, 'uploadImage']);
    Route::post('/uploadVideo', [MainController::class, 'uploadVideo']);

    // User management routes
    Route::get('/users', [AuthController::class, 'index']);
    Route::get('/users/{id}', [AuthController::class, 'show']);
    Route::get('/showAnotherProfile/{id}', [AuthController::class, 'showAnotherProfile']);
    Route::get('/doctorProfileGetPatients/{id}', [AuthController::class, 'doctorProfileGetPatients']);
    Route::get('/doctorProfileGetScoreHistory/{id}', [AuthController::class, 'doctorProfileGetScoreHistory']);
    Route::put('/users', [AuthController::class, 'update']);
    Route::put('/users/{id}', [AuthController::class, 'updateUserById']);
    Route::delete('/users/{id}', [AuthController::class, 'destroy']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/changePassword', [AuthController::class, 'changePassword']);
    Route::post('/upload-profile-image', [AuthController::class, 'uploadProfileImage']);
    Route::post('/uploadSyndicateCard', [AuthController::class, 'uploadSyndicateCard']);
    Route::post('/storeFCM', [NotificationController::class, 'storeFCM']);
    Route::post('/decryptedPassword', [AuthController::class, 'decryptedPassword']);

    // User locale/language preference routes
    Route::post('/user/locale', [UserLocaleController::class, 'updateLocale']);
    Route::get('/user/locale', [UserLocaleController::class, 'getLocale']);
    Route::get('/user/locale/test', [UserLocaleController::class, 'testLocaleResponse']);

    // Localized notifications routes
    Route::get('/notifications/localized', [LocalizedNotificationController::class, 'getAllNotifications']);
    Route::get('/notifications/localized/new', [LocalizedNotificationController::class, 'getNewNotifications']);
    Route::post('/notifications/localized/{id}/read', [LocalizedNotificationController::class, 'markAsRead']);
    Route::post('/notifications/localized/read-all', [LocalizedNotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/localized/test', [LocalizedNotificationController::class, 'testCreateLocalizedNotification']);

    // OTP routes
    Route::post('/emailverification', [OtpController::class, 'verifyOtp']);
    Route::post('/sendverificationmail', [OtpController::class, 'sendOtp']);
    Route::post('/resendemailverification', [OtpController::class, 'resendOtp']);

    // Role & Permission routes
    Route::post('/role', [AuthController::class, 'roletest']);
    Route::post('/createRoleAndPermission', [RolePermissionController::class, 'createRoleAndPermission']);
    Route::post('/assignRoleToUser', [RolePermissionController::class, 'assignRoleToUser']);
    Route::post('/checkPermission', [RolePermissionController::class, 'checkRoleAndPermission']);

    // Patient routes
    Route::post('/patient', [PatientsController::class, 'storePatient']);
    Route::get('/patient/{section_id}/{patient_id}', [SectionsController::class, 'showQuestionsAnswers']);
    Route::put('/patientsection/{patient_id}', [PatientsController::class, 'updateFinalSubmit']);
    Route::put('/patientsection/{section_id}/{patient_id}', [PatientsController::class, 'updatePatient']);
    Route::put('/submitStatus/{patient_id}', [SectionsController::class, 'updateFinalSubmit']);
    Route::get('/showSections/{patient_id}', [SectionsController::class, 'showSections']);
    Route::delete('/patient/{id}', [PatientsController::class, 'destroyPatient']);
    Route::post('/searchNew', [PatientsController::class, 'searchNew']);
    Route::get('/homeNew', [PatientsController::class, 'homeGetAllData']);
    Route::get('/currentPatientsNew', [PatientsController::class, 'doctorPatientGet']);
    Route::get('/allPatientsNew', [PatientsController::class, 'doctorPatientGetAll']);
    Route::get('/test', [PatientsController::class, 'test']);
    Route::post('/uploadFile', [PatientsController::class, 'uploadFile']);
    Route::post('/uploadFileNew', [PatientsController::class, 'uploadFileNew']);
    Route::get('/patientFilters', [PatientsController::class, 'patientFilterConditions']);
    Route::post('/patientFilters', [PatientsController::class, 'filteredPatients']);
    Route::post('/exportFilteredPatients', [PatientsController::class, 'exportFilteredPatients']);

    // Marked Patients routes
    Route::post('/markedPatients/{patient_id}', [PatientsController::class, 'markPatient']);
    Route::delete('/markedPatients/{patient_id}', [PatientsController::class, 'unmarkPatient']);
    Route::get('/markedPatients', [PatientsController::class, 'getMarkedPatients']);

    // Questions routes
    Route::get('/questions', [QuestionsController::class, 'index']);
    Route::post('/questions', [QuestionsController::class, 'store']);
    Route::get('/questions/{section_id}', [QuestionsController::class, 'show']);
    Route::get('/questions/{section_id}/{patient_id}', [QuestionsController::class, 'ShowQuestitionsAnswars']);
    Route::put('/questions/{id}', [QuestionsController::class, 'update']);
    Route::delete('/questions/{id}', [QuestionsController::class, 'destroy']);

    // Comment routes
    Route::get('/comment', [CommentController::class, 'index']);
    Route::post('/comment', [CommentController::class, 'store']);
    Route::get('/comment/{patient_id}', [CommentController::class, 'show']);
    Route::put('/comment/{patient_id}', [CommentController::class, 'update']);
    Route::delete('/comment/{patient_id}', [CommentController::class, 'destroy']);

    // Contact routes
    Route::get('/contact', [ContactController::class, 'index']);
    Route::post('/contact', [ContactController::class, 'store']);
    Route::get('/contact/{id}', [ContactController::class, 'show']);
    Route::put('/contact/{id}', [ContactController::class, 'update']);
    Route::delete('/contact/{id}', [ContactController::class, 'destroy']);

    // Post routes
    Route::get('/post', [PostsController::class, 'index']);
    Route::post('/post', [PostsController::class, 'store']);
    Route::get('/post/{id}', [PostsController::class, 'show']);
    Route::put('/post/{id}', [PostsController::class, 'update']);
    Route::delete('/post/{id}', [PostsController::class, 'destroy']);

    // Post Comments routes
    Route::get('/Postcomments', [PostCommentsController::class, 'index']);
    Route::post('/Postcomments', [PostCommentsController::class, 'store']);
    Route::get('/Postcomments/{id}', [PostCommentsController::class, 'show']);
    Route::put('/Postcomments/{id}', [PostCommentsController::class, 'update']);
    Route::delete('/Postcomments/{id}', [PostCommentsController::class, 'destroy']);

    // Notification routes
    Route::post('/notification', [NotificationController::class, 'store']);
    Route::get('/notification', [NotificationController::class, 'index']);
    Route::get('/shownotification', [NotificationController::class, 'showNew']);
    Route::put('/notification/{id}', [NotificationController::class, 'update']);
    Route::put('/notification', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notification/{id}', [NotificationController::class, 'destroy']);

    // Dose routes
    Route::get('/dose', [DoseController::class, 'index']);
    Route::post('/dose', [DoseController::class, 'store']);
    Route::get('/dose/{id}', [DoseController::class, 'show']);
    Route::put('/dose/{id}', [DoseController::class, 'update']);
    Route::delete('/dose/{id}', [DoseController::class, 'destroy']);
    Route::get('/dose/search/{query}', [DoseController::class, 'doseSearch']);

    // Achievement routes
    Route::get('/achievement', [AchievementController::class, 'index']);
    Route::post('/achievement', [AchievementController::class, 'store']);
    Route::get('/achievement/{id}', [AchievementController::class, 'show']);
    Route::put('/achievement/{id}', [AchievementController::class, 'update']);
    Route::delete('/achievement/{id}', [AchievementController::class, 'destroy']);

    // Consultation routes
    Route::post('/consultations', [ConsultationController::class, 'store']);
    Route::get('/consultations/sent', [ConsultationController::class, 'sentRequests']);
    Route::get('/consultations/received', [ConsultationController::class, 'receivedRequests']);
    Route::get('/consultations/{id}', [ConsultationController::class, 'consultationDetails']);
    Route::put('/consultations/{id}', [ConsultationController::class, 'update']);
    Route::post('/consultationDoctorSearch/{data}', [ConsultationController::class, 'consultationSearch']);

    // New consultation features (v2)
    Route::post('/consultations/{id}/add-doctors', [ConsultationController::class, 'addDoctors']);
    Route::put('/consultations/{id}/toggle-status', [ConsultationController::class, 'toggleStatus']);
    Route::get('/consultations/{id}/members', [ConsultationController::class, 'getMembers']);
    Route::post('/consultations/{id}/replies', [ConsultationController::class, 'addReply']);
    Route::delete('/consultations/{consultationId}/doctors/{doctorId}', [ConsultationController::class, 'removeDoctor']);

    // Achievement management routes
    Route::post('/achievements', [AchievementController::class, 'createAchievement']);
    Route::get('/achievements', [AchievementController::class, 'listAchievements']);
    Route::get('/users/{user}/achievements', [AchievementController::class, 'getUserAchievements']);
    Route::post('/checkAndAssignAchievementsForAllUsers', [AchievementController::class, 'checkAndAssignAchievementsForAllUsers']);

    // Feed Post routes
    Route::get('feed/posts', [FeedPostController::class, 'getFeedPosts']);
    Route::post('feed/posts', [FeedPostController::class, 'store']);
    Route::post('feed/posts/{id}', [FeedPostController::class, 'update']);
    Route::delete('feed/posts/{id}', [FeedPostController::class, 'destroy']);
    Route::post('feed/posts/{id}/likeOrUnlikePost', [FeedPostController::class, 'likeOrUnlikePost']);
    Route::post('feed/posts/{id}/saveOrUnsavePost', [FeedPostController::class, 'saveOrUnsavePost']);
    Route::post('feed/posts/{id}/comment', [FeedPostController::class, 'addComment']);
    Route::delete('feed/comments/{id}', [FeedPostController::class, 'deleteComment']);
    Route::get('posts/{postId}/likes', [FeedPostController::class, 'getPostLikes']);
    Route::get('posts/{postId}/comments', [FeedPostController::class, 'getPostComments']);
    Route::get('feed/posts/{id}', [FeedPostController::class, 'getPostById']);
    Route::post('comments/{commentId}/likeOrUnlikeComment', [FeedPostController::class, 'likeOrUnlikeComment']);
    Route::get('/feed/trendingPosts', [FeedPostController::class, 'trending']);
    Route::post('/feed/searchHashtags', [FeedPostController::class, 'searchHashtags']);
    Route::get('/feed/getPostsByHashtag/{hashtag}', [FeedPostController::class, 'getPostsByHashtag']);
    Route::post('/feed/searchPosts', [FeedPostController::class, 'searchPosts']);
    Route::get('/doctorposts/{doctorId}', [FeedPostController::class, 'getDoctorPosts']);
    Route::get('/doctorsavedposts/{doctorId}', [FeedPostController::class, 'getDoctorSavedPosts']);

    // Poll routes
    Route::post('/polls/{pollId}/vote', [PollController::class, 'voteUnvote']);
    Route::get('/polls/{pollId}/options/{optionId}/voters', [PollController::class, 'getVotersByOption']);
    Route::post('/polls/{pollId}/options', [PollController::class, 'addPollOption']);

    // Group routes
    Route::post('/groups', [GroupController::class, 'create']);
    Route::post('/groups/{id}', [GroupController::class, 'update']);
    Route::delete('/groups/{id}', [GroupController::class, 'delete']);
    Route::post('/groups/{groupId}/invite', [GroupController::class, 'inviteMember']);
    Route::post('/groups/{groupId}/invitation', [GroupController::class, 'handleInvitation']);
    Route::post('/groups/{groupId}/join-request', [GroupController::class, 'handleJoinRequest']);
    Route::get('/groups/{id}', [GroupController::class, 'show']);
    Route::post('/groups/{groupId}/removeMember', [GroupController::class, 'removeMember']);
    Route::post('/groups/{groupId}/searchMembers', [GroupController::class, 'searchMembers']);
    Route::get('/groups/{groupId}/members', [GroupController::class, 'fetchMembers']);
    Route::get('/groups/{groupId}/detailsWithPosts', [GroupController::class, 'fetchGroupDetailsWithPosts']);
    Route::post('/groups/{groupId}/join', [GroupController::class, 'joinGroup']);
    Route::post('/groups/{groupId}/leave', [GroupController::class, 'leaveGroup']);
    Route::get('/mygroups', [GroupController::class, 'fetchMyGroups']);
    Route::get('/groups', [GroupController::class, 'fetchAllGroups']);
    Route::get('/latest-groups-with-random-posts', [GroupController::class, 'fetchLatestGroupsWithRandomPosts']);
    Route::get('/groups/invitations/{doctorId}', [GroupController::class, 'getDoctorInvitations']);
    Route::get('/groups/{groupId}/invitations', [GroupController::class, 'getGroupInvitations']);

    // Chat/AI Consultation routes
    Route::post('/AIconsultation/{patientId}', [ChatController::class, 'sendConsultation']);
    Route::get('/AIconsultation-history/{patientId}', [ChatController::class, 'getConsultationHistory']);

    // Recommendation routes
    Route::get('/recommendations/{patient_id}', [RecommendationController::class, 'index']);
    Route::post('/recommendations/{patient_id}', [RecommendationController::class, 'store']);
    Route::put('/recommendations/{patient_id}', [RecommendationController::class, 'update']);
    Route::delete('/recommendations/{patient_id}', [RecommendationController::class, 'destroy']);

    // Share URLs
    Route::post('/share/generate', [ShareController::class, 'generateUrl']);
    Route::post('/share/bulk', [ShareController::class, 'generateBulkUrls']);
    Route::get('/share/preview', [ShareController::class, 'getPreview']);
});

// Authenticated user route with roles and permissions
Route::middleware(['auth:sanctum', 'check.blocked.home'])->get('/user', function (Request $request) {
    $user = $request->user();

    // Get user's single role (enforcing one role per user)
    $role = $user->roles()->first();
    $roleName = $role ? $role->name : null;

    // Get permissions from role only (not direct permissions)
    $permissions = $role ? $role->permissions()->pluck('name')->values() : collect();

    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'profile_completed' => $user->profile_completed,
        'avatar' => $user->avatar,
        'locale' => $user->locale,
        'role' => $roleName,
        'permissions' => $permissions,
        'created_at' => $user->created_at,
        'updated_at' => $user->updated_at,
    ];
});

// Get user role and permissions endpoint (used when permissions_changed is true)
Route::middleware(['auth:sanctum', 'check.blocked.home'])->get('/user/role-permissions', function (Request $request) {
    $user = $request->user();

    // Get user's single role (enforcing one role per user)
    $role = $user->roles()->first();
    $roleName = $role ? $role->name : null;

    // Get permissions from role only (not direct permissions)
    $permissions = $role ? $role->permissions()->pluck('name')->values() : collect();

    // Reset permissions_changed flag after fetching
    $user->update(['permissions_changed' => false]);

    return [
        'value' => true,
        'message' => 'Role and permissions retrieved successfully',
        'role' => $roleName,
        'permissions' => $permissions,
    ];
});
