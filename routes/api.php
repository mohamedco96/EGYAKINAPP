<?php

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\ForgetPasswordController;
use App\Modules\Auth\Controllers\ResetPasswordController;
use App\Modules\Auth\Controllers\EmailVerificationController;
use App\Modules\Auth\Controllers\OtpController;
use App\Http\Controllers\PatientHistoryController;
use App\Http\Controllers\ProductController;
use App\Modules\Patients\Controllers\PatientsController;
use App\Modules\Sections\Controllers\SectionsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\RecommendationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
| Public Routes:
| - POST /register: Register a new user.
| - POST /login: Login a user.
| - POST /forgotpassword: Send a password reset link.
| - POST /resetpasswordverification: Verify password reset token.
| - POST /resetpassword: Reset the user's password.
| - GET /generatePDF/{patient_id}: Generate a PDF for a patient.
| - GET /userPatient: Get the authenticated user's patient information.
| - POST /send-notification: Send a push notification.
| - POST /sendAllPushNotification: Send a push notification to all users.
|
| Settings Routes:
| - GET /settings: Get all settings.
| - POST /settings: Create a new setting.
| - GET /settings/{id}: Get a specific setting.
| - PUT /settings/{id}: Update a specific setting.
| - DELETE /settings/{id}: Delete a specific setting.
|
| Protected Routes (requires auth:sanctum middleware):
| - General:
|   - POST /uploadImage: Upload an image.
|   - POST /uploadVideo: Upload a video.
|
| - Users:
|   - GET /users: Get all users.
|   - GET /users/{id}: Get a specific user.
|   - GET /showAnotherProfile/{id}: Show another user's profile.
|   - GET /doctorProfileGetPatients/{id}: Get patients for a doctor.
|   - GET /doctorProfileGetScoreHistory/{id}: Get score history for a doctor.
|   - PUT /users: Update the authenticated user.
|   - PUT /users/{id}: Update a specific user.
|   - DELETE /users/{id}: Delete a specific user.
|   - POST /logout: Logout the authenticated user.
|   - POST /changePassword: Change the authenticated user's password.
|   - POST /upload-profile-image: Upload a profile image.
|   - POST /uploadSyndicateCard: Upload a syndicate card.
|   - POST /emailverification: Verify an email address.
|   - POST /sendverificationmail: Send an email verification link.
|   - POST /resendemailverification: Resend an email verification link.
|   - POST /storeFCM: Store FCM token for push notifications.
|
| - Role & Permission:
|   - POST /role: Test role functionality.
|   - POST /createRoleAndPermission: Create a role and permission.
|   - POST /assignRoleToUser: Assign a role to a user.
|   - POST /checkPermission: Check role and permission.
|
| - Patient:
|   - POST /patient: Create a new patient.
|   - GET /patient/{section_id}/{patient_id}: Get questions and answers for a patient section.
|   - PUT /patientsection/{patient_id}: Update final submit status for a patient.
|   - PUT /patientsection/{section_id}/{patient_id}: Update a patient section.
|   - PUT /submitStatus/{patient_id}: Update final submit status for a patient.
|   - GET /showSections/{patient_id}: Show sections for a patient.
|   - DELETE /patient/{id}: Delete a patient.
|   - POST /searchNew: Search for patients.
|   - GET /homeNew: Get all data for the home page.
|   - GET /currentPatientsNew: Get current patients for a doctor.
|   - GET /allPatientsNew: Get all patients for a doctor.
|   - GET /test: Test route.
|   - POST /uploadFile: Upload a file.
|   - POST /uploadFileNew: Upload a new file.
|   - GET /patientFilters: Get patient filter conditions.
|   - POST /patientFilters: Get filtered patients.
|
| - Questions:
|   - GET /questions: Get all questions.
|   - POST /questions: Create a new question.
|   - GET /questions/{section_id}: Get questions for a section.
|   - GET /questions/{section_id}/{patient_id}: Get questions and answers for a section and patient.
|   - PUT /questions/{id}: Update a question.
|   - DELETE /questions/{id}: Delete a question.
|
| - Comment:
|   - GET /comment: Get all comments.
|   - POST /comment: Create a new comment.
|   - GET /comment/{patient_id}: Get comments for a patient.
|   - PUT /comment/{patient_id}: Update a comment.
|   - DELETE /comment/{patient_id}: Delete a comment.
|
| - Contact:
|   - GET /contact: Get all contacts.
|   - POST /contact: Create a new contact.
|   - GET /contact/{id}: Get a specific contact.
|   - PUT /contact/{id}: Update a specific contact.
|   - DELETE /contact/{id}: Delete a specific contact.
|
| - Post:
|   - GET /post: Get all posts.
|   - POST /post: Create a new post.
|   - GET /post/{id}: Get a specific post.
|   - PUT /post/{id}: Update a specific post.
|   - DELETE /post/{id}: Delete a specific post.
|
| - PostComments:
|   - GET /Postcomments: Get all post comments.
|   - POST /Postcomments: Create a new post comment.
|   - GET /Postcomments/{id}: Get a specific post comment.
|   - PUT /Postcomments/{id}: Update a specific post comment.
|   - DELETE /Postcomments/{id}: Delete a specific post comment.
|
| - AppNotification:
|   - POST /notification: Create a new notification.
|   - GET /notification: Get all notifications.
|   - GET /shownotification: Get new notifications.
|   - PUT /notification/{id}: Update a specific notification.
|   - PUT /notification: Mark all notifications as read.
|   - DELETE /notification/{id}: Delete a specific notification.
|
| - Dose:
|   - GET /dose: Get all doses.
|   - POST /dose: Create a new dose.
|   - GET /dose/{id}: Get a specific dose.
|   - PUT /dose/{id}: Update a specific dose.
|   - DELETE /dose/{id}: Delete a specific dose.
|
| - Achievement:
|   - GET /achievement: Get all achievements.
|   - POST /achievement: Create a new achievement.
|   - GET /achievement/{id}: Get a specific achievement.
|   - PUT /achievement/{id}: Update a specific achievement.
|   - DELETE /achievement/{id}: Delete a specific achievement.
|
| - Consultations:
|   - POST /consultations: Create a new consultation.
|   - GET /consultations/sent: Get sent consultation requests.
|   - GET /consultations/received: Get received consultation requests.
|   - GET /consultations/{id}: Get consultation details.
|   - PUT /consultations/{id}: Update a consultation.
|   - POST /consultationDoctorSearch/{data}: Search for a doctor for consultation.
|
| - Achievements:
|   - POST /achievements: Create a new achievement.
|   - GET /achievements: Get all achievements.
|   - GET /users/{user}/achievements: Get achievements for a specific user.
|   - POST /checkAndAssignAchievementsForAllUsers: Check and assign achievements for all users.
|
| - Feed Post Routes:
|   - GET /feed/posts: Get all feed posts.
|   - POST /feed/posts: Create a new feed post.
|   - PUT /feed/posts/{id}: Update a specific feed post.
|   - DELETE /feed/posts/{id}: Delete a specific feed post.
|   - POST /feed/posts/{id}/likeOrUnlikePost: Like or unlike a feed post.
|   - POST /feed/posts/{id}/saveOrUnsavePost: Save or unsave a feed post.
|   - POST /feed/posts/{id}/comment: Add a comment to a feed post.
|   - DELETE /feed/comments/{id}: Delete a comment from a feed post.
|   - GET /posts/{postId}/likes: Get likes for a post.
|   - GET /posts/{postId}/comments: Get comments for a post.
|   - GET /feed/posts/{id}: Get a specific feed post.
|   - POST /posts/{postId}/comments: Add a comment to a post.
|   - POST /comments/{commentId}/likeOrUnlikeComment: Like or unlike a comment.
|   - GET /feed/trendingPosts: Get trending feed posts.
|
| - Groups:
|   - POST /groups: Create a new group.
|   - POST /groups/{id}: Update a specific group.
|   - DELETE /groups/{id}: Delete a specific group.
|   - POST /groups/{groupId}/invite: Invite a member to a group.
|   - POST /groups/{groupId}/invitation: Handle a group invitation.
|   - GET /groups/{id}: Get a specific group.
|   - POST /groups/{groupId}/removeMember: Remove a member from a group.
|   - POST /groups/{groupId}/searchMembers: Search for members in a group.
|   - GET /groups/{groupId}/members: Get members of a group.
|   - GET /groups/{groupId}/detailsWithPosts: Get group details with posts.
|   - POST /groups/{groupId}/join: Join a group.
|   - POST /groups/{groupId}/leave: Leave a group.
|   - GET /mygroups: Get the authenticated user's groups.
|   - GET /groups: Get all groups.
|
| - Recommendations:
|   - GET /recommendations/{patient_id}: Get recommendations for a patient.
|   - POST /recommendations/{patient_id}: Create a new recommendation for a patient.
|   - PUT /recommendations/{patient_id}: Update an existing recommendation for a patient.
|   - DELETE /recommendations/{patient_id}: Delete a recommendation for a patient.
|
| Fallback Route:
| - Returns a 404 response if the route does not exist.
|
| Authenticated User Route:
| - GET /user: Get the authenticated user's information.
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgotpassword', [ForgetPasswordController::class, 'forgotPassword']);
Route::post('/resetpasswordverification', [ResetPasswordController::class, 'resetpasswordverification']);
Route::post('/resetpassword', [ResetPasswordController::class, 'resetpassword']);
Route::get('/generatePDF/{patient_id}', [PatientsController::class, 'generatePatientPDF']);
Route::post('/send-notification', [AuthController::class, 'sendPushNotificationTest']);
Route::post('/sendAllPushNotification', 'NotificationController@sendAllPushNotification');

// routes/api.php
Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail']);
Route::post('/email/verify', [EmailVerificationController::class, 'verifyEmail']);

// Settings
Route::get('/settings', 'SettingsController@index');
Route::post('/settings', 'SettingsController@store');
Route::get('/settings/{id}', 'SettingsController@show');
Route::put('/settings/{id}', 'SettingsController@update');
Route::delete('/settings/{id}', 'SettingsController@destroy');

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
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
    // Route::post('/emailverification', [EmailVerificationController::class, 'verifyEmail']);
    // Route::post('/sendverificationmail', [EmailVerificationController::class, 'sendVerificationEmail']);
    // Route::post('/resendemailverification', [EmailVerificationController::class, 'sendVerificationEmail']);
    Route::post('/storeFCM', 'NotificationController@storeFCM');
    Route::post('/decryptedPassword', [AuthController::class, 'decryptedPassword']);
    

    Route::post('/emailverification', [OtpController::class, 'verifyOtp']);
    Route::post('/sendverificationmail', [OtpController::class, 'sendOtp']);
    Route::post('/resendemailverification', [OtpController::class, 'resendOtp']);

    // Route::post('/send-otp', [OtpController::class, 'sendOtp']);
    // Route::post('/verify-otp', [OtpController::class, 'verifyOtp']);
    // Route::post('/resend-otp', [OtpController::class, 'resendOtp']);

    // Role & Permission
    Route::post('/role', [AuthController::class, 'roletest']);
    Route::post('/createRoleAndPermission', 'RolePermissionController@createRoleAndPermission');
    Route::post('/assignRoleToUser', 'RolePermissionController@assignRoleToUser');
    Route::post('/checkPermission', 'RolePermissionController@checkRoleAndPermission');

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

    // Questions
    Route::get('/questions', 'QuestionsController@index');
    Route::post('/questions', 'QuestionsController@store');
    Route::get('/questions/{section_id}', 'QuestionsController@show');
    Route::get('/questions/{section_id}/{patient_id}', 'QuestionsController@ShowQuestitionsAnswars');
    Route::put('/questions/{id}', 'QuestionsController@update');
    Route::delete('/questions/{id}', 'QuestionsController@destroy');

    // Comment
    Route::get('/comment', 'CommentController@index');
    Route::post('/comment', 'CommentController@store');
    Route::get('/comment/{patient_id}', 'CommentController@show');
    Route::put('/comment/{patient_id}', 'CommentController@update');
    Route::delete('/comment/{patient_id}', 'CommentController@destroy');

    // Contact
    Route::get('/contact', 'ContactController@index');
    Route::post('/contact', 'ContactController@store');
    Route::get('/contact/{id}', 'ContactController@show');
    Route::put('/contact/{id}', 'ContactController@update');
    Route::delete('/contact/{id}', 'ContactController@destroy');

    // Post
    Route::get('/post', 'PostsController@index');
    Route::post('/post', 'PostsController@store');
    Route::get('/post/{id}', 'PostsController@show');
    Route::put('/post/{id}', 'PostsController@update');
    Route::delete('/post/{id}', 'PostsController@destroy');

    // PostComments
    Route::get('/Postcomments', 'PostCommentsController@index');
    Route::post('/Postcomments', 'PostCommentsController@store');
    Route::get('/Postcomments/{id}', 'PostCommentsController@show');
    Route::put('/Postcomments/{id}', 'PostCommentsController@update');
    Route::delete('/Postcomments/{id}', 'PostCommentsController@destroy');

    // AppNotification
    Route::post('/notification', 'NotificationController@store');
    Route::get('/notification', 'NotificationController@show');
    Route::get('/shownotification', 'NotificationController@showNew');
    Route::put('/notification/{id}', 'NotificationController@update');
    Route::put('/notification', 'NotificationController@markAllAsRead');
    Route::delete('/notification/{id}', 'NotificationController@destroy');

    // Dose
    Route::get('/dose', 'DoseController@index');
    Route::post('/dose', 'DoseController@store');
    Route::get('/dose/{id}', 'DoseController@show');
    Route::put('/dose/{id}', 'DoseController@update');
    Route::delete('/dose/{id}', 'DoseController@destroy');
    Route::get('/dose/search/{query}', 'DoseController@doseSearch');
    // Achievement
    Route::get('/achievement', 'AchievementController@index');
    Route::post('/achievement', 'AchievementController@store');
    Route::get('/achievement/{id}', 'AchievementController@show');
    Route::put('/achievement/{id}', 'AchievementController@update');
    Route::delete('/achievement/{id}', 'AchievementController@destroy');

    // Consultations
    Route::post('/consultations', 'ConsultationController@store');
    Route::get('/consultations/sent', 'ConsultationController@sentRequests');
    Route::get('/consultations/received', 'ConsultationController@receivedRequests');
    Route::get('/consultations/{id}', 'ConsultationController@consultationDetails');
    Route::put('/consultations/{id}', 'ConsultationController@update');
    Route::post('/consultationDoctorSearch/{data}', 'ConsultationController@consultationSearch');

    // Achievements
    Route::post('/achievements', 'AchievementController@createAchievement');
    Route::get('/achievements', 'AchievementController@listAchievements');
    Route::get('/users/{user}/achievements', 'AchievementController@getUserAchievements');
    Route::post('/checkAndAssignAchievementsForAllUsers', 'AchievementController@checkAndAssignAchievementsForAllUsers');

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

    Route::post('/AIconsultation/{patientId}', 'ChatController@sendConsultation');
    Route::get('/AIconsultation-history/{patientId}', 'ChatController@getConsultationHistory');
    

    // Recommendations
    Route::get('/recommendations/{patient_id}', 'RecommendationController@index');
    Route::post('/recommendations/{patient_id}', 'RecommendationController@store');
    Route::put('/recommendations/{patient_id}', 'RecommendationController@update');
    Route::delete('/recommendations/{patient_id}', 'RecommendationController@destroy');

});

Route::fallback(function () {
    return response()->json([
        'value' => false,
        'message' => 'Page does not exist',
    ], 404);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
