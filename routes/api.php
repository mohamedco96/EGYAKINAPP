<?php
use App\Http\controllers\ProductController;
use App\Http\controllers\AuthController;
use App\Http\controllers\PatientHistoryController;
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

//Route::post('/register',[AuthController::class,'register']);
//Route::post('/login',[AuthController::class,'login']);


//protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    //PatientHistory
    Route::get('/PatientHistory','PatientHistoryController@index');
    Route::post('/PatientHistory','PatientHistoryController@store');
    Route::get('/PatientHistory/{id}','PatientHistoryController@show');
    Route::put('/PatientHistory/{id}','PatientHistoryController@update');
    Route::delete('/PatientHistory/{id}','PatientHistoryController@destroy');




    Route::get('/products/search/{name}','ProductController@search');
    Route::post('/logout','AuthController@logout');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
