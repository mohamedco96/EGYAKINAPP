<?php

use App\Http\Controllers\Api\V1\AchievementController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\ConsultationController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DoseController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\FeedPostController;
use App\Http\Controllers\Api\V1\ForgetPasswordController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\LocalizationTestController;
use App\Http\Controllers\Api\V1\LocalizedNotificationController;
use App\Http\Controllers\Api\V1\MainController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OtpController;
use App\Http\Controllers\Api\V1\PatientsController;
use App\Http\Controllers\Api\V1\PollController;
use App\Http\Controllers\Api\V1\PostCommentsController;
use App\Http\Controllers\Api\V1\PostsController;
use App\Http\Controllers\Api\V1\QuestionsController;
use App\Http\Controllers\Api\V1\RecommendationController;
use App\Http\Controllers\Api\V1\ResetPasswordController;
use App\Http\Controllers\Api\V1\RolePermissionController;
use App\Http\Controllers\Api\V1\SectionsController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\ShareController;
use App\Http\Controllers\Api\V1\UserLocaleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| This file contains all the API routes for version 1 of the application.
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned the "api" middleware group and "v1" prefix.
|
*/

// Public routes for V1
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgotpassword', [ForgetPasswordController::class, 'forgotPassword']);
    Route::post('/resetpasswordverification', [ResetPasswordController::class, 'resetpasswordverification']);
    Route::post('/resetpassword', [ResetPasswordController::class, 'resetpassword']);
});

// Localization test routes (development and staging only — never production)
if (! app()->isProduction()) {
    Route::get('/localization/test', [LocalizationTestController::class, 'test']);
    Route::post('/localization/login', [LocalizationTestController::class, 'login']);
    Route::post('/localization/patient', [LocalizationTestController::class, 'createPatient']);
    Route::post('/localization/points', [LocalizationTestController::class, 'awardPoints']);
}

// Email verification routes
Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail']);
Route::post('/email/verify', [EmailVerificationController::class, 'verifyEmail']);

// Settings read routes (public)
Route::get('/settings', [SettingsController::class, 'index']);
Route::get('/settings/{settings}', [SettingsController::class, 'show']);

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

    // New consultation features (v1)
    Route::post('/consultations/{id}/add-doctors', [ConsultationController::class, 'addDoctors']);
    Route::put('/consultations/{id}/toggle-status', [ConsultationController::class, 'toggleStatus']);
    Route::get('/consultations/{id}/members', [ConsultationController::class, 'getMembers']);
    Route::post('/consultations/{id}/replies', [ConsultationController::class, 'addReply']);
    Route::delete('/consultations/{consultationId}/doctors/{doctorId}', [ConsultationController::class, 'removeDoctor']);

    // Achievement management routes
    Route::post('/achievements', [AchievementController::class, 'createAchievement']);
    Route::get('/achievements', [AchievementController::class, 'listAchievements']);
    Route::get('/users/{userId}/achievements', [AchievementController::class, 'getUserAchievements']);
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

    // Patient PDF (requires auth — patient data is sensitive)
    Route::get('/generatePDF/{patient_id}', [PatientsController::class, 'generatePatientPDF']);

    // Settings write routes (requires auth)
    Route::post('/settings', [SettingsController::class, 'store']);
    Route::put('/settings/{settings}', [SettingsController::class, 'update']);
    Route::delete('/settings/{settings}', [SettingsController::class, 'destroy']);

    // Admin-only routes
    Route::middleware('role:admin|super-admin')->group(function () {
        Route::post('/sendAllPushNotification', [NotificationController::class, 'sendAllPushNotification']);
    });
});

// Authenticated user route
Route::middleware(['auth:sanctum', 'check.blocked.home'])->get('/user', function (Request $request) {
    return $request->user();
});
