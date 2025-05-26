<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\PatientsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pdf', function () {
    return view('patient_pdf');
});

Route::get('/policy', function () {
    return view('policy');
});

Route::get('/ChatGPT', function () {
    return view('chat');
});

Route::post('/chat', [ChatController::class, 'chat']);

Route::get('/realTimeSearch', [PatientsController::class, 'realTimeSearch'])->name('realTimeSearch');
Route::get('/search', function () {
    return view('search');
});

Route::get('/post/{id}', function ($id) {
    // Check if the request is from a mobile device
    $isMobile = preg_match('/(android|iphone|ipad|mobile)/i', request()->header('User-Agent'));

    if ($isMobile) {
        // Redirect to the app's custom scheme (e.g., "egyakin://post/837")
        return redirect()->away("egyakin://post/$id");
    } else {
        // Fallback for web browsers/API calls (no view needed)
        return response()->json([
            'error' => 'not_found',
            'message' => 'This is a deep link. Open the app to view the content.',
        ], 404);
    }
});

// test
