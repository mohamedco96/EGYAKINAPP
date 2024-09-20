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
*/

// Public routes
Route::post('/register', 'AuthController@register');
Route::post('/login', 'AuthController@login');
Route::post('/forgotpassword', 'ForgetPasswordController@forgotPassword');
Route::post('/resetpasswordverification', 'ResetPasswordController@resetpasswordverification');
Route::post('/resetpassword', 'ResetPasswordController@resetpassword');

Route::get('/generatePDF/{patient_id}', 'PatientsController@generatePatientPDF');

Route::get('/userPatient', 'AuthController@userPatient');

Route::post('/send-notification', 'NotificationController@send');

// Settings
Route::get('/settings', 'SettingsController@index');
Route::post('/settings', 'SettingsController@store');
Route::get('/settings/{id}', 'SettingsController@show');
Route::put('/settings/{id}', 'SettingsController@update');
Route::delete('/settings/{id}', 'SettingsController@destroy');

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
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
