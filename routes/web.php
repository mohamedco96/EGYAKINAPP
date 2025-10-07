<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DeepLinkController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\SocialAuthController;
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

Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics')->middleware('locale');

// Export routes
Route::middleware(['auth'])->group(function () {
    Route::post('/export/patients/start', [ExportController::class, 'startPatientsExport'])->name('export.patients.start');
    Route::get('/export/progress/{filename}', [ExportController::class, 'checkExportProgress'])->name('export.progress');
    Route::get('/export/download/{filename}', [ExportController::class, 'downloadExport'])->name('export.download');
});

// Deeplink routes with URL previews
Route::get('/post/{id}', [DeepLinkController::class, 'post'])->name('deeplink.post');
Route::get('/patient/{id}', [DeepLinkController::class, 'patient'])->name('deeplink.patient');
Route::get('/group/{id}', [DeepLinkController::class, 'group'])->name('deeplink.group');
Route::get('/consultation/{id}', [DeepLinkController::class, 'consultation'])->name('deeplink.consultation');

// test

// === EMAIL REPORTING TEST ROUTES (Remove in production) ===
Route::get('/test-daily-report', function () {
    return new DailyReportMail;
});

Route::get('/test-weekly-summary', function () {
    return new WeeklySummaryMail;
});

Route::get('/test-send-daily', function () {
    try {
        Mail::to('mohamedco215@gmail.com')->send(new DailyReportMail);

        return response()->json(['success' => true, 'message' => 'Daily report sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Error: '.$e->getMessage()]);
    }
});

Route::get('/test-send-weekly', function () {
    try {
        Mail::to('mohamedco215@gmail.com')->send(new WeeklySummaryMail);

        return response()->json(['success' => true, 'message' => 'Weekly summary sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Error: '.$e->getMessage()]);
    }
});

// === SOCIAL AUTHENTICATION TEST ROUTES ===
Route::get('/apple-signin-test', function () {
    return view('apple-signin-test');
})->name('apple.signin.test');

// Social OAuth redirect routes
Route::get('/auth/social/google', [SocialAuthController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/social/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('google.callback');
Route::get('/auth/social/apple', [SocialAuthController::class, 'redirectToApple'])->name('apple.redirect');
Route::get('/auth/social/apple/callback', [SocialAuthController::class, 'handleAppleCallback'])->name('apple.callback');
