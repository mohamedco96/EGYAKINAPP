<?php
use App\Http\controllers\ProductController;
use App\Http\controllers\AuthController;
use App\Http\controllers\PatientHistoryController;
use App\Http\controllers\SectionController;
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

Route::post('/register','AuthController@register');
Route::post('/login','AuthController@login');

Route::get('/userPatient','AuthController@userPatient');

//Route::post('/register',[AuthController::class,'register']);
//Route::post('/login',[AuthController::class,'login']);


//protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {

    //Users
    Route::get('/users','AuthController@index');
    Route::get('/users/{id}','AuthController@show');
    Route::post('/logout','AuthController@logout');

    //PatientHistory
    Route::get('/patientHistory','PatientHistoryController@index');
    Route::post('/patientHistory','PatientHistoryController@store');
    Route::get('/patientHistory/{id}','PatientHistoryController@show');
    Route::put('/patientHistory/{id}','PatientHistoryController@update');
    Route::delete('/patientHistory/{id}','PatientHistoryController@destroy');
    Route::get('/allPatients','PatientHistoryController@doctorPatientGetAll');
    Route::get('/currentPatients','PatientHistoryController@doctorPatientGet');

    //PatientHistory
    Route::get('/complaint','ComplaintController@index');
    Route::post('/complaint','ComplaintController@store');
    Route::get('/complaint/{id}','ComplaintController@show');
    Route::put('/complaint/{id}','ComplaintController@update');
    Route::delete('/complaint/{id}','ComplaintController@destroy');

    //Section
    Route::get('/section','SectionController@index');
    Route::get('/section/{id}','SectionController@show');
    Route::delete('/section/{id}','SectionController@destroy');

    Route::get('/patient/search/{name}','PatientHistoryController@search');

    Route::get('/products/search/{name}','ProductController@search');
});

Route::fallback(function(){
    $response = [
        'value' => false,
        'message' => 'Page does not exist'
    ];
    return response($response, 404);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
