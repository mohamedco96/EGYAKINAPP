<?php

use App\Http\Controllers\Api\V1\AchievementController;
use App\Http\Controllers\SocialAuthController;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\EmailVerificationController;
use App\Modules\Auth\Controllers\ForgetPasswordController;
use App\Modules\Auth\Controllers\OtpController;
use App\Modules\Auth\Controllers\ResetPasswordController;
use App\Modules\Chat\Controllers\ChatController;
use App\Modules\Patients\Controllers\PatientsController;
use App\Modules\Recommendations\Controllers\RecommendationController;
use App\Modules\RolePermission\Controllers\RolePermissionController;
use App\Modules\Sections\Controllers\SectionsController;
use App\Modules\Settings\Controllers\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes with Versioning Support
|--------------------------------------------------------------------------
|
| This file contains both versioned and backward-compatible routes.
| The versioned routes are organized by version (v1, v2, etc.) while
| maintaining the original non-versioned routes for backward compatibility.
|
*/

// ==============================================================================
// VERSIONED API ROUTES
// ==============================================================================

// Version 1 Routes (api/v1/...)
Route::prefix('v1')->group(function () {
    // Load all V1 routes from the separate file
    require __DIR__.'/api/v1.php';
});

// Version 2 Routes (api/v2/...) - All new changes should use V2
Route::prefix('v2')->group(function () {
    require __DIR__.'/api/v2.php';
});

// ==============================================================================
// BACKWARD COMPATIBILITY ROUTES (Original non-versioned routes)
// ==============================================================================
// These routes maintain the original API endpoints without version prefixes
// to ensure existing production applications continue to work

// Public routes
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgotpassword', [ForgetPasswordController::class, 'forgotPassword']);
    Route::post('/resetpasswordverification', [ResetPasswordController::class, 'resetpasswordverification']);
    Route::post('/resetpassword', [ResetPasswordController::class, 'resetpassword']);
});

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


// routes/api.php
Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail']);
Route::post('/email/verify', [EmailVerificationController::class, 'verifyEmail']);

// Settings read routes (public)
Route::get('/settings', [SettingsController::class, 'index']);
Route::get('/settings/{settings}', [SettingsController::class, 'show']);

// Protected routes
Route::group(['middleware' => ['auth:sanctum', 'check.blocked.home']], function () {
    // General
    Route::post('/uploadImage', 'MainController@uploadImage');
    Route::post('/uploadVideo', 'MainController@uploadVideo');

    // Users
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
    Route::post('/storeFCM', [\App\Modules\Notifications\Controllers\NotificationController::class, 'storeFCM']);

    Route::post('/emailverification', [OtpController::class, 'verifyOtp']);
    Route::post('/sendverificationmail', [OtpController::class, 'sendOtp']);
    Route::post('/resendemailverification', [OtpController::class, 'resendOtp']);

    // Role & Permission
    Route::post('/role', [AuthController::class, 'roletest']);
    Route::post('/createRoleAndPermission', [RolePermissionController::class, 'createRoleAndPermission']);
    Route::post('/assignRoleToUser', [RolePermissionController::class, 'assignRoleToUser']);
    Route::post('/checkPermission', [RolePermissionController::class, 'checkRoleAndPermission']);

    // Patient
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

    // Questions - Using modular structure
    Route::get('/questions', [\App\Modules\Questions\Controllers\QuestionsController::class, 'index']);
    Route::post('/questions', [\App\Modules\Questions\Controllers\QuestionsController::class, 'store']);
    Route::get('/questions/{section_id}', [\App\Modules\Questions\Controllers\QuestionsController::class, 'show']);
    Route::get('/questions/{section_id}/{patient_id}', [\App\Modules\Questions\Controllers\QuestionsController::class, 'ShowQuestitionsAnswars']);
    Route::put('/questions/{id}', [\App\Modules\Questions\Controllers\QuestionsController::class, 'update']);
    Route::delete('/questions/{id}', [\App\Modules\Questions\Controllers\QuestionsController::class, 'destroy']);

    // Comment - Using modular structure
    Route::get('/comment', [\App\Modules\Comments\Controllers\CommentController::class, 'index']);
    Route::post('/comment', [\App\Modules\Comments\Controllers\CommentController::class, 'store']);
    Route::get('/comment/{patient_id}', [\App\Modules\Comments\Controllers\CommentController::class, 'show']);
    Route::put('/comment/{patient_id}', [\App\Modules\Comments\Controllers\CommentController::class, 'update']);
    Route::delete('/comment/{patient_id}', [\App\Modules\Comments\Controllers\CommentController::class, 'destroy']);

    // Contact
    Route::get('/contact', [\App\Modules\Contacts\Controllers\ContactController::class, 'index']);
    Route::post('/contact', [\App\Modules\Contacts\Controllers\ContactController::class, 'store']);
    Route::get('/contact/{id}', [\App\Modules\Contacts\Controllers\ContactController::class, 'show']);
    Route::put('/contact/{id}', [\App\Modules\Contacts\Controllers\ContactController::class, 'update']);
    Route::delete('/contact/{id}', [\App\Modules\Contacts\Controllers\ContactController::class, 'destroy']);

    // Post - Using modular structure
    Route::get('/post', [\App\Modules\Posts\Controllers\PostsController::class, 'index']);
    Route::post('/post', [\App\Modules\Posts\Controllers\PostsController::class, 'store']);
    Route::get('/post/{id}', [\App\Modules\Posts\Controllers\PostsController::class, 'show']);
    Route::put('/post/{id}', [\App\Modules\Posts\Controllers\PostsController::class, 'update']);
    Route::delete('/post/{id}', [\App\Modules\Posts\Controllers\PostsController::class, 'destroy']);

    // PostComments - Using modular structure
    Route::get('/Postcomments', [\App\Modules\Posts\Controllers\PostCommentsController::class, 'index']);
    Route::post('/Postcomments', [\App\Modules\Posts\Controllers\PostCommentsController::class, 'store']);
    Route::get('/Postcomments/{id}', [\App\Modules\Posts\Controllers\PostCommentsController::class, 'show']);
    Route::put('/Postcomments/{id}', [\App\Modules\Posts\Controllers\PostCommentsController::class, 'update']);
    Route::delete('/Postcomments/{id}', [\App\Modules\Posts\Controllers\PostCommentsController::class, 'destroy']);

    // AppNotification - Using modular structure
    Route::post('/notification', [\App\Modules\Notifications\Controllers\NotificationController::class, 'store']);
    Route::get('/notification', [\App\Modules\Notifications\Controllers\NotificationController::class, 'index']);
    Route::get('/shownotification', [\App\Modules\Notifications\Controllers\NotificationController::class, 'showNew']);
    Route::put('/notification/{id}', [\App\Modules\Notifications\Controllers\NotificationController::class, 'update']);
    Route::put('/notification', [\App\Modules\Notifications\Controllers\NotificationController::class, 'markAllAsRead']);
    Route::delete('/notification/{id}', [\App\Modules\Notifications\Controllers\NotificationController::class, 'destroy']);

    // Dose - Using modular structure
    Route::get('/dose', [\App\Modules\Doses\Controllers\DoseController::class, 'index']);
    Route::post('/dose', [\App\Modules\Doses\Controllers\DoseController::class, 'store']);
    Route::get('/dose/{id}', [\App\Modules\Doses\Controllers\DoseController::class, 'show']);
    Route::put('/dose/{id}', [\App\Modules\Doses\Controllers\DoseController::class, 'update']);
    Route::delete('/dose/{id}', [\App\Modules\Doses\Controllers\DoseController::class, 'destroy']);
    Route::get('/dose/search/{query}', [\App\Modules\Doses\Controllers\DoseController::class, 'doseSearch']);
    // Achievement
    Route::get('/achievement', [AchievementController::class, 'index']);
    Route::post('/achievement', [AchievementController::class, 'store']);
    Route::get('/achievement/{id}', [AchievementController::class, 'show']);
    Route::put('/achievement/{id}', [AchievementController::class, 'update']);
    Route::delete('/achievement/{id}', [AchievementController::class, 'destroy']);

    // Consultations - Using modular structure
    Route::post('/consultations', [\App\Modules\Consultations\Controllers\ConsultationController::class, 'store']);
    Route::get('/consultations/sent', [\App\Modules\Consultations\Controllers\ConsultationController::class, 'sentRequests']);
    Route::get('/consultations/received', [\App\Modules\Consultations\Controllers\ConsultationController::class, 'receivedRequests']);
    Route::get('/consultations/{id}', [\App\Modules\Consultations\Controllers\ConsultationController::class, 'consultationDetails']);
    Route::put('/consultations/{id}', [\App\Modules\Consultations\Controllers\ConsultationController::class, 'update']);
    Route::post('/consultationDoctorSearch/{data}', [\App\Modules\Consultations\Controllers\ConsultationController::class, 'consultationSearch']);

    // Achievements
    Route::post('/achievements', [AchievementController::class, 'createAchievement']);
    Route::get('/achievements', [AchievementController::class, 'listAchievements']);
    Route::get('/users/{userId}/achievements', [AchievementController::class, 'getUserAchievements']);
    Route::post('/checkAndAssignAchievementsForAllUsers', [AchievementController::class, 'checkAndAssignAchievementsForAllUsers']);

    // Feed Post Routes
    Route::get('feed/posts', 'FeedPostController@getFeedPosts');
    Route::post('feed/posts', 'FeedPostController@store');
    Route::post('feed/posts/{id}', 'FeedPostController@update');
    Route::delete('feed/posts/{id}', 'FeedPostController@destroy');
    Route::post('feed/posts/{id}/likeOrUnlikePost', 'FeedPostController@likeOrUnlikePost');
    Route::post('feed/posts/{id}/saveOrUnsavePost', 'FeedPostController@saveOrUnsavePost');
    Route::post('feed/posts/{id}/comment', 'FeedPostController@addComment');
    Route::delete('feed/comments/{id}', 'FeedPostController@deleteComment');
    Route::get('posts/{postId}/likes', 'FeedPostController@getPostLikes');
    Route::get('posts/{postId}/comments', 'FeedPostController@getPostComments');
    Route::get('feed/posts/{id}', 'FeedPostController@getPostById');
    // Route::post('posts/{postId}/comments', 'FeedPostController@addComment');
    Route::post('comments/{commentId}/likeOrUnlikeComment', 'FeedPostController@likeOrUnlikeComment');
    Route::get('/feed/trendingPosts', 'FeedPostController@trending');
    // Search Hashtags
    Route::post('/feed/searchHashtags', 'FeedPostController@searchHashtags');
    Route::get('/feed/getPostsByHashtag/{hashtag}', 'FeedPostController@getPostsByHashtag');
    // Search Posts
    Route::post('/feed/searchPosts', 'FeedPostController@searchPosts');
    Route::get('/doctorposts/{doctorId}', 'FeedPostController@getDoctorPosts');
    Route::get('/doctorsavedposts/{doctorId}', 'FeedPostController@getDoctorSavedPosts');
    Route::post('/polls/{pollId}/vote', 'PollController@voteUnvote');
    Route::get('/polls/{pollId}/options/{optionId}/voters', 'PollController@getVotersByOption');
    Route::post('/polls/{pollId}/options', 'PollController@addPollOption');

    // Groups
    Route::post('/groups', 'GroupController@create');
    Route::post('/groups/{id}', 'GroupController@update');
    Route::delete('/groups/{id}', 'GroupController@delete');
    Route::post('/groups/{groupId}/invite', 'GroupController@inviteMember');
    Route::post('/groups/{groupId}/invitation', 'GroupController@handleInvitation');
    Route::post('/groups/{groupId}/join-request', 'GroupController@handleJoinRequest');
    Route::get('/groups/{id}', 'GroupController@show');
    Route::post('/groups/{groupId}/removeMember', 'GroupController@removeMember');
    Route::post('/groups/{groupId}/searchMembers', 'GroupController@searchMembers');
    Route::get('/groups/{groupId}/members', 'GroupController@fetchMembers');
    Route::get('/groups/{groupId}/detailsWithPosts', 'GroupController@fetchGroupDetailsWithPosts');
    Route::post('/groups/{groupId}/join', 'GroupController@joinGroup');
    Route::post('/groups/{groupId}/leave', 'GroupController@leaveGroup');
    Route::get('/mygroups', 'GroupController@fetchMyGroups');
    Route::get('/groups', 'GroupController@fetchAllGroups');
    Route::get('/latest-groups-with-random-posts', 'GroupController@fetchLatestGroupsWithRandomPosts');
    Route::get('/groups/invitations/{doctorId}', 'GroupController@getDoctorInvitations');
    Route::get('/groups/{groupId}/invitations', 'GroupController@getGroupInvitations');

    Route::post('/AIconsultation/{patientId}', [ChatController::class, 'sendConsultation']);
    Route::get('/AIconsultation-history/{patientId}', [ChatController::class, 'getConsultationHistory']);

    // Recommendations
    Route::get('/recommendations/{patient_id}', [RecommendationController::class, 'index']);
    Route::post('/recommendations/{patient_id}', [RecommendationController::class, 'store']);
    Route::put('/recommendations/{patient_id}', [RecommendationController::class, 'update']);
    Route::delete('/recommendations/{patient_id}', [RecommendationController::class, 'destroy']);

    // Patient PDF (requires auth — patient data is sensitive)
    Route::get('/generatePDF/{patient_id}', [PatientsController::class, 'generatePatientPDF']);

    // Settings write routes (requires auth)
    Route::post('/settings', [SettingsController::class, 'store']);
    Route::put('/settings/{settings}', [SettingsController::class, 'update']);
    Route::delete('/settings/{settings}', [SettingsController::class, 'destroy']);

    // Admin-only routes
    Route::middleware('role:admin|super-admin')->group(function () {
        Route::post('/sendAllPushNotification', [\App\Modules\Notifications\Controllers\NotificationController::class, 'sendAllPushNotification']);
    });
});

Route::fallback(function () {
    return response()->json([
        'value' => false,
        'message' => 'Page does not exist',
    ], 404);
});

Route::middleware(['auth:sanctum', 'check.blocked.home'])->get('/user', function (Request $request) {
    return $request->user();
});
