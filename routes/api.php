<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientHistoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

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
| - POST /forgotpassword: Request a password reset.
| - POST /resetpasswordverification: Verify password reset.
| - POST /resetpassword: Reset the password.
| - GET /generatePDF/{patient_id}: Generate a PDF for a patient.
| - GET /userPatient: Get user patient information.
| - POST /send-notification: Send a push notification.
| - POST /sendAllPushNotification: Send push notifications to all users.
|
| Settings Routes:
| - GET /settings: Get all settings.
| - POST /settings: Store new settings.
| - GET /settings/{id}: Get settings by ID.
| - PUT /settings/{id}: Update settings by ID.
| - DELETE /settings/{id}: Delete settings by ID.
|
| Protected Routes (requires auth:sanctum middleware):
| - General:
|   - POST /uploadImage: Upload an image.
|   - POST /uploadVideo: Upload a video.
|
| - Users:
|   - GET /users: Get all users.
|   - GET /users/{id}: Get user by ID.
|   - GET /showAnotherProfile/{id}: Show another user's profile.
|   - GET /doctorProfileGetPatients/{id}: Get patients for a doctor.
|   - GET /doctorProfileGetScoreHistory/{id}: Get score history for a doctor.
|   - PUT /users: Update user information.
|   - PUT /users/{id}: Update user by ID.
|   - DELETE /users/{id}: Delete user by ID.
|   - POST /logout: Logout a user.
|   - POST /changePassword: Change user password.
|   - POST /upload-profile-image: Upload profile image.
|   - POST /uploadSyndicateCard: Upload syndicate card.
|   - POST /emailverification: Verify email.
|   - POST /sendverificationmail: Send email verification.
|   - POST /resendemailverification: Resend email verification.
|   - POST /storeFCM: Store FCM token.
|
| - Role & Permission:
|   - POST /role: Test role.
|   - POST /createRoleAndPermission: Create role and permission.
|   - POST /assignRoleToUser: Assign role to user.
|   - POST /checkPermission: Check role and permission.
|
| - Patient:
|   - POST /patient: Store patient information.
|   - GET /patient/{section_id}/{patient_id}: Show questions and answers for a patient.
|   - PUT /patientsection/{patient_id}: Update final submit for a patient.
|   - PUT /patientsection/{section_id}/{patient_id}: Update patient information.
|   - PUT /submitStatus/{patient_id}: Update final submit status.
|   - GET /showSections/{patient_id}: Show sections for a patient.
|   - DELETE /patient/{id}: Delete patient by ID.
|   - POST /searchNew: Search for new patients.
|   - GET /homeNew: Get all data for home.
|   - GET /currentPatientsNew: Get current patients for a doctor.
|   - GET /allPatientsNew: Get all patients for a doctor.
|   - GET /test: Test route.
|   - POST /uploadFile: Upload a file.
|   - POST /uploadFileNew: Upload a new file.
|   - GET /patientFilters: Get patient filter conditions.
|   - POST /patientFilters: Filter patients.
|
| - Questions:
|   - GET /questions: Get all questions.
|   - POST /questions: Store new questions.
|   - GET /questions/{section_id}: Get questions by section ID.
|   - GET /questions/{section_id}/{patient_id}: Show questions and answers for a section and patient.
|   - PUT /questions/{id}: Update question by ID.
|   - DELETE /questions/{id}: Delete question by ID.
|
| - Comment:
|   - GET /comment: Get all comments.
|   - POST /comment: Store new comment.
|   - GET /comment/{patient_id}: Get comments by patient ID.
|   - PUT /comment/{patient_id}: Update comment by patient ID.
|   - DELETE /comment/{patient_id}: Delete comment by patient ID.
|
| - Contact:
|   - GET /contact: Get all contacts.
|   - POST /contact: Store new contact.
|   - GET /contact/{id}: Get contact by ID.
|   - PUT /contact/{id}: Update contact by ID.
|   - DELETE /contact/{id}: Delete contact by ID.
|
| - Post:
|   - GET /post: Get all posts.
|   - POST /post: Store new post.
|   - GET /post/{id}: Get post by ID.
|   - PUT /post/{id}: Update post by ID.
|   - DELETE /post/{id}: Delete post by ID.
|
| - PostComments:
|   - GET /Postcomments: Get all post comments.
|   - POST /Postcomments: Store new post comment.
|   - GET /Postcomments/{id}: Get post comment by ID.
|   - PUT /Postcomments/{id}: Update post comment by ID.
|   - DELETE /Postcomments/{id}: Delete post comment by ID.
|
| - AppNotification:
|   - POST /notification: Store new notification.
|   - GET /notification: Show notification.
|   - GET /shownotification: Show new notifications.
|   - PUT /notification/{id}: Update notification by ID.
|   - PUT /notification: Mark all notifications as read.
|   - DELETE /notification/{id}: Delete notification by ID.
|
| - Dose:
|   - GET /dose: Get all doses.
|   - POST /dose: Store new dose.
|   - GET /dose/{id}: Get dose by ID.
|   - PUT /dose/{id}: Update dose by ID.
|   - DELETE /dose/{id}: Delete dose by ID.
|
| - Achievement:
|   - GET /achievement: Get all achievements.
|   - POST /achievement: Store new achievement.
|   - GET /achievement/{id}: Get achievement by ID.
|   - PUT /achievement/{id}: Update achievement by ID.
|   - DELETE /achievement/{id}: Delete achievement by ID.
|
| - Consultations:
|   - POST /consultations: Store new consultation.
|   - GET /consultations/sent: Get sent consultation requests.
|   - GET /consultations/received: Get received consultation requests.
|   - GET /consultations/{id}: Get consultation details by ID.
|   - PUT /consultations/{id}: Update consultation by ID.
|   - POST /consultationDoctorSearch/{data}: Search for consultation doctor.
|
| - Achievements:
|   - POST /achievements: Create new achievement.
|   - GET /achievements: List all achievements.
|   - GET /users/{user}/achievements: Get user achievements.
|   - POST /checkAndAssignAchievementsForAllUsers: Check and assign achievements for all users.
|
| - Feed Post Routes:
|   - GET /feed/posts: Get all feed posts.
|   - POST /feed/posts: Store new feed post.
|   - PUT /feed/posts/{id}: Update feed post by ID.
|   - DELETE /feed/posts/{id}: Delete feed post by ID.
|   - POST /feed/posts/{id}/likeOrUnlikePost: Like or unlike a feed post.
|   - POST /feed/posts/{id}/saveOrUnsavePost: Save or unsave a feed post.
|   - POST /feed/posts/{id}/comment: Add comment to a feed post.
|   - DELETE /feed/comments/{id}: Delete comment by ID.
|   - GET /posts/{postId}/likes: Get likes for a post.
|   - GET /posts/{postId}/comments: Get comments for a post.
|   - GET /feed/posts/{id}: Get feed post by ID.
|   - POST /posts/{postId}/comments: Add comment or reply to a post.
|   - POST /comments/{commentId}/likeOrUnlikeComment: Like or unlike a comment.
|   - GET /feed/trendingPosts: Get trending posts.
|
| - Groups:
|   - POST /groups: Create a new group.
|   - PUT /groups/{id}: Update group by ID.
|   - DELETE /groups/{id}: Delete group by ID.
|   - POST /groups/{id}/invite: Invite to group.
|   - POST /invitations/{id}/accept: Accept group invitation.
|   - POST /invitations/{id}/decline: Decline group invitation.
|   - POST /posts: Create a new post.
|
| Fallback Route:
| - Returns a 404 response if the route does not exist.
|
| Authenticated User Route:
| - GET /user: Get authenticated user information.
|
*/

// Public routes
Route::post('/register', 'AuthController@register');
Route::post('/login', 'AuthController@login');
Route::post('/forgotpassword', 'ForgetPasswordController@forgotPassword');
Route::post('/resetpasswordverification', 'ResetPasswordController@resetpasswordverification');
Route::post('/resetpassword', 'ResetPasswordController@resetpassword');

Route::get('/generatePDF/{patient_id}', 'PatientsController@generatePatientPDF');

Route::get('/userPatient', 'AuthController@userPatient');

Route::post('/send-notification', 'AuthController@sendPushNotificationTest');
Route::post('/sendAllPushNotification', 'NotificationController@sendAllPushNotification');

// Settings
Route::get('/settings', 'SettingsController@index');
Route::post('/settings', 'SettingsController@store');
Route::get('/settings/{id}', 'SettingsController@show');
Route::put('/settings/{id}', 'SettingsController@update');
Route::delete('/settings/{id}', 'SettingsController@destroy');

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    //General
    Route::post('/uploadImage', 'MainController@uploadImage');
    Route::post('/uploadVideo', 'MainController@uploadVideo');

    // Users
    Route::get('/users', 'AuthController@index');
    Route::get('/users/{id}', 'AuthController@show');
    Route::get('/showAnotherProfile/{id}', 'AuthController@showAnotherProfile');
    Route::get('/doctorProfileGetPatients/{id}', 'AuthController@doctorProfileGetPatients');
    Route::get('/doctorProfileGetScoreHistory/{id}', 'AuthController@doctorProfileGetScoreHistory');
    Route::put('/users', 'AuthController@update');
    Route::put('/users/{id}', 'AuthController@updateUserById');
    Route::delete('/users/{id}', 'AuthController@destroy');
    Route::post('/logout', 'AuthController@logout');
    Route::post('/changePassword', 'AuthController@changePassword');
    Route::post('/upload-profile-image', 'AuthController@uploadProfileImage');
    Route::post('/uploadSyndicateCard', 'AuthController@uploadSyndicateCard');
    Route::post('/emailverification', 'EmailVerificationController@email_verification');
    Route::post('/sendverificationmail', 'EmailVerificationController@sendEmailVerification');
    Route::post('/resendemailverification', 'EmailVerificationController@sendEmailVerification');
    Route::post('/storeFCM', 'NotificationController@storeFCM');

    // Role & Permission
    Route::post('/role', 'AuthController@roletest');
    Route::post('/createRoleAndPermission', 'RolePermissionController@createRoleAndPermission');
    Route::post('/assignRoleToUser', 'RolePermissionController@assignRoleToUser');
    Route::post('/checkPermission', 'RolePermissionController@checkRoleAndPermission');

    // Patient
    Route::post('/patient', 'PatientsController@storePatient');
    Route::get('/patient/{section_id}/{patient_id}', 'SectionsController@showQuestionsAnswers');
    Route::put('/patientsection/{patient_id}', 'PatientsController@updateFinalSubmit');
    Route::put('/patientsection/{section_id}/{patient_id}', 'PatientsController@updatePatient');
    Route::put('/submitStatus/{patient_id}', 'SectionsController@updateFinalSubmit');
    Route::get('/showSections/{patient_id}', 'SectionsController@showSections');
    Route::delete('/patient/{id}', 'PatientsController@destroyPatient');
    Route::post('/searchNew', 'PatientsController@searchNew');
    Route::get('/homeNew', 'PatientsController@homeGetAllData');
    Route::get('/currentPatientsNew', 'PatientsController@doctorPatientGet');
    Route::get('/allPatientsNew', 'PatientsController@doctorPatientGetAll');
    Route::get('/test', 'PatientsController@test');
//    Route::get('/generatePDF/{patient_id}', 'PatientsController@generatePatientPDF');
    Route::post('/uploadFile', 'PatientsController@uploadFile');
    Route::post('/uploadFileNew', 'PatientsController@uploadFileNew');
    Route::get('/patientFilters', 'PatientsController@patientFilterConditions');
    Route::post('/patientFilters', 'PatientsController@filteredPatients');



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

    // Achievement
    Route::get('/achievement', 'AchievementController@index');
    Route::post('/achievement', 'AchievementController@store');
    Route::get('/achievement/{id}', 'AchievementController@show');
    Route::put('/achievement/{id}', 'AchievementController@update');
    Route::delete('/achievement/{id}', 'AchievementController@destroy');

    //Consultations
    Route::post('/consultations', 'ConsultationController@store');
    Route::get('/consultations/sent', 'ConsultationController@sentRequests');
    Route::get('/consultations/received', 'ConsultationController@receivedRequests');
    Route::get('/consultations/{id}', 'ConsultationController@consultationDetails');
    Route::put('/consultations/{id}','ConsultationController@update');
    Route::post('/consultationDoctorSearch/{data}', 'ConsultationController@consultationSearch');

    //Achievements
    Route::post('/achievements', 'AchievementController@createAchievement');
    Route::get('/achievements', 'AchievementController@listAchievements');
    Route::get('/users/{user}/achievements', 'AchievementController@getUserAchievements');
    Route::post('/checkAndAssignAchievementsForAllUsers', 'AchievementController@checkAndAssignAchievementsForAllUsers');



//    New


        // Feed Post Routes
        //Route::get('feed/posts', 'FeedPostController@index');
        Route::get('feed/posts', 'FeedPostController@getFeedPosts');
        Route::post('feed/posts', 'FeedPostController@store');
        Route::put('feed/posts/{id}', 'FeedPostController@update');
        Route::delete('feed/posts/{id}', 'FeedPostController@destroy');

        // Like and Unlike Route
        Route::post('feed/posts/{id}/likeOrUnlikePost', 'FeedPostController@likeOrUnlikePost');

        // Save and Unsave Route
        Route::post('feed/posts/{id}/saveOrUnsavePost', 'FeedPostController@saveOrUnsavePost');

        // Comment Routes
        Route::post('feed/posts/{id}/comment', 'FeedPostController@addComment');
        Route::delete('feed/comments/{id}', 'FeedPostController@deleteComment');

        // Get post likes
        Route::get('posts/{postId}/likes', 'FeedPostController@getPostLikes');

        // Get post comments
        Route::get('posts/{postId}/comments', 'FeedPostController@getPostComments');

        // Get post by id
        Route::get('feed/posts/{id}', 'FeedPostController@getPostById');

        // Add comment or reply
        Route::post('posts/{postId}/comments', 'FeedPostController@addComment');

        // likeOrUnlikeComment
        Route::post('comments/{commentId}/likeOrUnlikeComment', 'FeedPostController@likeOrUnlikeComment');

        Route::get('/feed/trendingPosts', 'FeedPostController@trending');

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
        // Join and Leave Group Routes
        Route::post('/groups/{groupId}/join', 'GroupController@joinGroup');
        Route::post('/groups/{groupId}/leave', 'GroupController@leaveGroup');
        // Fetch My Groups
        Route::get('/mygroups', 'GroupController@fetchMyGroups');

        // Fetch All Groups
        Route::get('/groups', 'GroupController@fetchAllGroups');
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
