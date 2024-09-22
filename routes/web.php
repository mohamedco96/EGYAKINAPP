<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PatientsController;

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

