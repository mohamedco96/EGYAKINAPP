<?php
use App\Http\controllers\ProductController;
use App\Http\controllers\AuthController;
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

//Route::apiresource('products',ProductController::class);

//Public routes
//Route::get('/products/search/{name}',[ProductController::class,'search']);

Route::post('/register','AuthController@register');
Route::post('/login','AuthController@login');

//Route::post('/register',[AuthController::class,'register']);
//Route::post('/login',[AuthController::class,'login']);


//protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/products/search/{name}','ProductController@search');
    Route::post('/logout','AuthController@logout');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
