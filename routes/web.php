<?php

use App\Mail\DailyReportMail;
use App\Mail\WeeklySummaryMail;
use App\Modules\Chat\Controllers\ChatController;
use App\Modules\Patients\Controllers\PatientsController;
use Illuminate\Support\Facades\Mail;
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

// Route::post('/chat', [ChatController::class, 'chat']); // Method doesn't exist, commented out

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

// === EMAIL REPORTING TEST ROUTES (Remove in production) ===
Route::get('/test-daily-report', function () {
    return new DailyReportMail();
});

Route::get('/test-weekly-summary', function () {
    return new WeeklySummaryMail();
});

Route::get('/test-send-daily', function () {
    try {
        Mail::to('mohamedco215@gmail.com')->send(new DailyReportMail());

        return response()->json(['success' => true, 'message' => 'Daily report sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Error: '.$e->getMessage()]);
    }
});

Route::get('/test-send-weekly', function () {
    try {
        Mail::to('mohamedco215@gmail.com')->send(new WeeklySummaryMail());

        return response()->json(['success' => true, 'message' => 'Weekly summary sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Error: '.$e->getMessage()]);
    }
});
