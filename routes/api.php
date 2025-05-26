<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API Version 1 Routes
Route::prefix('v1')->group(function () {
    // Public routes with strict rate limiting
    Route::group(['middleware' => 'throttle:5,1'], function () {
        Route::post('/register', 'AuthController@register');
        Route::post('/login', ['middleware' => 'throttle.login', 'uses' => 'AuthController@login']);
        Route::post('/forgotpassword', ['middleware' => 'throttle:3,1', 'uses' => 'ForgetPasswordController@forgotPassword']);
        Route::post('/resetpasswordverification', ['middleware' => 'throttle:3,1', 'uses' => 'ResetPasswordController@resetpasswordverification']);
        Route::post('/resetpassword', ['middleware' => 'throttle:3,1', 'uses' => 'ResetPasswordController@resetpassword']);
        Route::post('/email/verification-notification', 'EmailVerificationController@sendVerificationEmail');
        Route::post('/email/verify', 'EmailVerificationController@verifyEmail');
    });

    // Protected routes with granular rate limiting
    Route::group(['middleware' => ['auth:sanctum']], function () {
        // Admin-only routes
        Route::group(['middleware' => ['role:admin', 'throttle:20,1']], function () {
            // Settings management
            Route::get('/settings', 'SettingsController@index');
            Route::post('/settings', 'SettingsController@store');
            Route::get('/settings/{id}', 'SettingsController@show');
            Route::put('/settings/{id}', 'SettingsController@update');
            Route::delete('/settings/{id}', 'SettingsController@destroy');

            // Role & Permission management
            Route::post('/createRoleAndPermission', 'RolePermissionController@createRoleAndPermission');
            Route::post('/assignRoleToUser', 'RolePermissionController@assignRoleToUser');

            // Global notifications
            Route::post('/sendAllPushNotification', 'NotificationController@sendAllPushNotification');
        });

        // Doctor-only routes
        Route::group(['middleware' => ['role:doctor', 'throttle:20,1']], function () {
            // Patient data access
            Route::get('/generatePDF/{patient_id}', 'PatientsController@generatePatientPDF');
            Route::get('/userPatient', 'AuthController@userPatient');
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
            Route::post('/uploadFile', ['middleware' => ['validate.file', 'throttle:5,1'], 'uses' => 'PatientsController@uploadFile']);
            Route::post('/uploadFileNew', ['middleware' => ['validate.file', 'throttle:5,1'], 'uses' => 'PatientsController@uploadFileNew']);
            Route::get('/patientFilters', 'PatientsController@patientFilterConditions');
            Route::post('/patientFilters', 'PatientsController@filteredPatients');
        });

        // Sensitive operations with stricter rate limiting
        Route::group(['middleware' => 'throttle:5,1'], function () {
            Route::post('/changePassword', 'AuthController@changePassword');
            Route::post('/upload-profile-image', [
                'middleware' => ['validate.image', 'throttle:3,1'],
                'uses' => 'AuthController@uploadProfileImage',
            ]);
            Route::post('/uploadSyndicateCard', [
                'middleware' => ['validate.image', 'throttle:3,1'],
                'uses' => 'AuthController@uploadSyndicateCard',
            ]);
            Route::post('/uploadFile', [
                'middleware' => ['validate.file', 'throttle:3,1'],
                'uses' => 'PatientsController@uploadFile',
            ]);
            Route::post('/uploadFileNew', [
                'middleware' => ['validate.file', 'throttle:3,1'],
                'uses' => 'PatientsController@uploadFileNew',
            ]);
        });

        // General operations with standard rate limiting
        Route::group(['middleware' => 'throttle:30,1'], function () {
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
            Route::post('/storeFCM', 'NotificationController@storeFCM');

            Route::post('/emailverification', 'OtpController@verifyOtp');
            Route::post('/sendverificationmail', 'OtpController@sendOtp');
            Route::post('/resendemailverification', 'OtpController@resendOtp');

            Route::post('/send-otp', 'OtpController@sendOtp');
            Route::post('/verify-otp', 'OtpController@verifyOtp');
            Route::post('/resend-otp', 'OtpController@resendOtp');

            // Role & Permission
            Route::post('/role', 'AuthController@roletest');
            Route::post('/checkPermission', 'RolePermissionController@checkRoleAndPermission');

            // Patient
            Route::get('/test', 'PatientsController@test');

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
            Route::post('/dose/search', 'DoseController@doseSearch');
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
            Route::post('comments/{commentId}/likeOrUnlikeComment', 'FeedPostController@likeOrUnlikeComment');
            Route::get('/feed/trendingPosts', 'FeedPostController@trending');
            Route::post('/feed/searchHashtags', 'FeedPostController@searchHashtags');
            Route::get('/feed/getPostsByHashtag/{hashtag}', 'FeedPostController@getPostsByHashtag');
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
    });
});

// API Version 2 Routes (for future use)
Route::prefix('v2')->group(function () {
    // Future v2 routes will go here
    
});

// Legacy routes (temporary, for backward compatibility)
Route::group(['middleware' => 'throttle:5,1'], function () {
    Route::post('/register', 'AuthController@register');
    Route::post('/login', ['middleware' => 'throttle.login', 'uses' => 'AuthController@login']);
    Route::post('/forgotpassword', ['middleware' => 'throttle:3,1', 'uses' => 'ForgetPasswordController@forgotPassword']);
    Route::post('/resetpasswordverification', ['middleware' => 'throttle:3,1', 'uses' => 'ResetPasswordController@resetpasswordverification']);
    Route::post('/resetpassword', ['middleware' => 'throttle:3,1', 'uses' => 'ResetPasswordController@resetpassword']);
    Route::post('/email/verification-notification', 'EmailVerificationController@sendVerificationEmail');
    Route::post('/email/verify', 'EmailVerificationController@verifyEmail');
});

// Fallback route
Route::fallback(function () {
    return response()->json([
        'value' => false,
        'message' => 'API version not found. Please use a valid API version (e.g., /v1/)',
    ], 404);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
