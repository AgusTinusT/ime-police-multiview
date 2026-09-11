<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PoliceCommandController;
use App\Http\Controllers\OfficerManagementController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TacChannelController;
use Inertia\Inertia;

// Main Police Tactical Command Center Dashboard
Route::get('/', [PoliceCommandController::class, 'dashboard'])->name('home');
Route::get('/dashboard', [PoliceCommandController::class, 'dashboard'])->name('dashboard');

// Dedicated Standalone Pages
Route::get('/officers', [PoliceCommandController::class, 'officers'])->name('officers.index');
// Route::get('/radio-codes', [PoliceCommandController::class, 'radioCodes'])->name('radio-codes');
Route::get('/about', [PoliceCommandController::class, 'about'])->name('about');
Route::get('/feedback', [PoliceCommandController::class, 'feedbackPage'])->name('feedback');

// Admin direct slash route (hidden access)
Route::get('/admin', function () {
    return auth()->check() ? redirect()->route('admin.officers') : redirect()->route('login');
})->name('admin');

// Dedicated Standalone Admin Command Hub (Protected by Auth)
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/officers', [OfficerManagementController::class, 'adminPage'])->name('admin.officers');
    Route::post('/api/sync-streams', [OfficerManagementController::class, 'syncStreams'])->name('admin.sync-streams');
    Route::post('/api/sync-subscribers', [OfficerManagementController::class, 'syncSubscribers'])->name('admin.sync-subscribers');
    Route::post('/api/check-channel', [OfficerManagementController::class, 'checkChannel'])->name('admin.check-channel');
});

// Redirect any attempt to access /register to home
Route::get('/register', function () {
    return redirect()->route('home');
});

// REST API Endpoints (Public Stream Telemetry & Visitor Feedback)
Route::prefix('api/v1')->group(function () {
    Route::get('/streams', [PoliceCommandController::class, 'apiStreams']);
    Route::post('/sync', [PoliceCommandController::class, 'apiSync']);
    Route::match(['get', 'post'], '/search-live', [PoliceCommandController::class, 'apiSearchLive']);
    Route::get('/stream-details', [PoliceCommandController::class, 'apiStreamDetails']);
    Route::match(['get', 'post'], '/telemetry', [PoliceCommandController::class, 'apiTelemetry']);
    Route::post('/feedback', [FeedbackController::class, 'submit'])->middleware('throttle:5,1');
    
    // TAC Tactical Radio Channels
    Route::prefix('tac')->group(function () {
        Route::get('/', [TacChannelController::class, 'index']);
        Route::post('/assign', [TacChannelController::class, 'assign']);
        Route::post('/remove', [TacChannelController::class, 'remove']);
        Route::post('/extend', [TacChannelController::class, 'extend']);
        Route::post('/clear', [TacChannelController::class, 'clear']);
    });
});


// Admin-Protected Officer Master Management API (MySQL)
Route::middleware('auth')->prefix('api/v1/officers')->group(function () {
    Route::get('/', [OfficerManagementController::class, 'index']);
    Route::post('/', [OfficerManagementController::class, 'store']);
    Route::put('/{id}', [OfficerManagementController::class, 'update']);
    Route::delete('/{id}', [OfficerManagementController::class, 'destroy']);
    Route::patch('/{id}/toggle', [OfficerManagementController::class, 'toggle']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
