<?php

use App\Http\controllers\AuthController;
use App\Http\controllers\PatientHistoryController;
use App\Http\controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

//Route::apiresource('PatientHistory',PatientHistoryController::class);

//Public routes
//Route::get('/products/search/{name}',[ProductController::class,'search']);

Route::post('/register', 'AuthController@register');
Route::post('/login', 'AuthController@login');
Route::post('/forgotpassword', 'ForgetPasswordController@forgotPassword');
Route::post('/resetpasswordverification', 'ResetPasswordController@resetpasswordverification');
Route::post('/resetpassword', 'ResetPasswordController@resetpassword');

Route::get('/userPatient', 'AuthController@userPatient');
Route::post('/chat', 'ChatController@chat');

//Route::post('/register',[AuthController::class,'register']);
//Route::post('/login',[AuthController::class,'login']);

//protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {

    //Users
    Route::get('/users', 'AuthController@index');
    Route::get('/users/{id}', 'AuthController@show');
    Route::put('/users/{id}', 'AuthController@update');
    Route::delete('/users/{id}', 'AuthController@destroy');
    Route::post('/logout', 'AuthController@logout');
    Route::post('/emailverification', 'EmailVerificationController@email_verification');
    Route::post('/sendverificationmail', 'EmailVerificationController@sendEmailVerification');
    Route::post('/resendemailverification', 'EmailVerificationController@sendEmailVerification');

    //PatientHistory
    Route::get('/patientHistory', 'PatientHistoryController@index');
    Route::post('/patientHistory', 'PatientHistoryController@store');
    Route::get('/patientHistory/{id}', 'PatientHistoryController@show');
    Route::put('/patientHistory/{id}', 'PatientHistoryController@update');
    Route::delete('/patientHistory/{id}', 'PatientHistoryController@destroy');
    Route::get('/allPatients', 'PatientHistoryController@doctorPatientGetAll');
    Route::get('/allPatientsnew', 'PatientHistoryController@doctorPatientGetAllnew');
    Route::get('/currentPatients', 'PatientHistoryController@doctorPatientGet');
    Route::get('/patient/search/{name}', 'PatientHistoryController@search');

    //Questions
    Route::get('/questions', 'QuestionsController@index');
    Route::post('/questions', 'QuestionsController@store');
    Route::get('/questions/{section_id}', 'QuestionsController@show');
    Route::get('/questions/{section_id}/{patient_id}', 'QuestionsController@ShowQuestitionsAnswars');
    Route::put('/questions/{id}', 'QuestionsController@update');
    Route::delete('/questions/{id}', 'QuestionsController@destroy');

    //complaint
    Route::get('/complaint', 'ComplaintController@index');
    Route::post('/complaint', 'ComplaintController@store');
    Route::get('/complaint/{id}', 'ComplaintController@show');
    Route::put('/complaint/{id}', 'ComplaintController@update');
    Route::delete('/complaint/{id}', 'ComplaintController@destroy');

    //Cause of AKI
    Route::get('/cause', 'CauseController@index');
    Route::post('/cause', 'CauseController@store');
    Route::get('/cause/{id}', 'CauseController@show');
    Route::put('/cause/{id}', 'CauseController@update');
    Route::delete('/cause/{id}', 'CauseController@destroy');

    //Risk factors for AKI
    Route::get('/risk', 'RiskController@index');
    Route::post('/risk', 'RiskController@store');
    Route::get('/risk/{id}', 'RiskController@show');
    Route::put('/risk/{id}', 'RiskController@update');
    Route::delete('/risk/{id}', 'RiskController@destroy');

    //Assessment of the patient
    Route::get('/assessment', 'AssessmentController@index');
    Route::post('/assessment', 'AssessmentController@store');
    Route::get('/assessment/{id}', 'AssessmentController@show');
    Route::put('/assessment/{id}', 'AssessmentController@update');
    Route::delete('/assessment/{id}', 'AssessmentController@destroy');

    //Medical examinations
    Route::get('/examination', 'ExaminationController@index');
    Route::post('/examination', 'ExaminationController@store');
    Route::get('/examination/{id}', 'ExaminationController@show');
    Route::put('/examination/{id}', 'ExaminationController@update');
    Route::delete('/examination/{id}', 'ExaminationController@destroy');

    //Section
    Route::get('/section', 'SectionController@index');
    Route::get('/section/{patient_id}', 'SectionController@show');
    Route::delete('/section/{id}', 'SectionController@destroy');
    Route::put('/section/{patient_id}', 'SectionController@updateFinalSubmit');
    Route::put('/section/{section_id}/{patient_id}', 'SectionController@update');

    //Comment
    Route::get('/comment', 'CommentController@index');
    Route::post('/comment', 'CommentController@store');
    Route::get('/comment/{patient_id}', 'CommentController@show');
    Route::put('/comment/{patient_id}', 'CommentController@update');
    Route::delete('/comment/{patient_id}', 'CommentController@destroy');
    Route::post('/like', 'LikesController@like');
    Route::post('/unlike', 'LikesController@unlike');

    //contact
    Route::get('/contact', 'ContactController@index');
    Route::post('/contact', 'ContactController@store');
    Route::get('/contact/{id}', 'ContactController@show');
    Route::put('/contact/{id}', 'ContactController@update');
    Route::delete('/contact/{id}', 'ContactController@destroy');

    //Decision
    Route::get('/decision', 'DecisionController@index');
    Route::post('/decision', 'DecisionController@store');
    Route::get('/decision/{id}', 'DecisionController@show');
    Route::put('/decision/{id}', 'DecisionController@update');
    Route::delete('/decision/{id}', 'DecisionController@destroy');

    //Outcome
    Route::get('/outcome', 'OutcomeController@index');
    Route::post('/outcome', 'OutcomeController@store');
    Route::get('/outcome/{patient_id}', 'OutcomeController@show');
    Route::put('/outcome/{patient_id}', 'OutcomeController@update');
    Route::delete('/outcome/{patient_id}', 'OutcomeController@destroy');

    //Post
    Route::get('/post', 'PostsController@index');
    Route::post('/post', 'PostsController@store');
    Route::get('/post/{id}', 'PostsController@show');
    Route::put('/post/{id}', 'PostsController@update');
    Route::delete('/post/{id}', 'PostsController@destroy');

    //PostComments
    Route::get('/Postcomments', 'PostCommentsController@index');
    Route::post('/Postcomments', 'PostCommentsController@store');
    Route::get('/Postcomments/{id}', 'PostCommentsController@show');
    Route::put('/Postcomments/{id}', 'PostCommentsController@update');
    Route::delete('/Postcomments/{id}', 'PostCommentsController@destroy');

    //Notification
    //Route::get('/notification','NotificationController@index');
    Route::post('/notification', 'NotificationController@store');
    Route::get('/notification', 'NotificationController@show');
    Route::put('/notification', 'NotificationController@update');
    Route::delete('/notification/{id}', 'NotificationController@destroy');
});

Route::fallback(function () {
    $response = [
        'value' => false,
        'message' => 'Page does not exist',
    ];

    return response($response, 404);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
