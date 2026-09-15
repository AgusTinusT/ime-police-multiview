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
Route::get('/about', [PoliceCommandController::class, 'about'])->name('about');
Route::get('/qna', [PoliceCommandController::class, 'qnaPage'])->name('qna');
Route::get('/feedback', [PoliceCommandController::class, 'feedbackPage'])->name('feedback');

// Admin direct slash route (hidden access)
Route::get('/admin', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    return auth()->user()->isAdmin() ? redirect()->route('admin.officers') : redirect()->route('dashboard');
})->name('admin');

// Dedicated Standalone Admin Command Hub (Protected by Auth & Admin Role)
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/officers', [OfficerManagementController::class, 'adminPage'])->name('admin.officers');
    Route::post('/api/sync-streams', [OfficerManagementController::class, 'syncStreams'])->name('admin.sync-streams');
    Route::post('/api/sync-subscribers', [OfficerManagementController::class, 'syncSubscribers'])->name('admin.sync-subscribers');
    Route::post('/api/check-channel', [OfficerManagementController::class, 'checkChannel'])->name('admin.check-channel');
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
Route::middleware(['auth', 'admin'])->prefix('api/v1/officers')->group(function () {
    Route::get('/', [OfficerManagementController::class, 'index']);
    Route::post('/', [OfficerManagementController::class, 'store']);
    Route::put('/{id}', [OfficerManagementController::class, 'update']);
    Route::delete('/{id}', [OfficerManagementController::class, 'destroy']);
    Route::patch('/{id}/toggle', [OfficerManagementController::class, 'toggle']);
});

// Admin-Protected Announcements API
use App\Http\Controllers\AnnouncementController;
Route::middleware(['auth', 'admin'])->prefix('api/v1/announcements')->group(function () {
    Route::get('/', [AnnouncementController::class, 'index']);
    Route::post('/', [AnnouncementController::class, 'store']);
    Route::put('/{id}', [AnnouncementController::class, 'update']);
    Route::delete('/{id}', [AnnouncementController::class, 'destroy']);
    Route::patch('/{id}/toggle', [AnnouncementController::class, 'toggle']);
});

Route::get('/api/v1/active-announcements', [AnnouncementController::class, 'getActive']);

// Authenticated User Cloud Watchlist API
use App\Http\Controllers\UserWatchlistController;
Route::middleware('auth')->prefix('api/v1/user/watchlist')->group(function () {
    Route::get('/', [UserWatchlistController::class, 'index']);
    Route::post('/toggle', [UserWatchlistController::class, 'toggle']);
});

// Admin-Protected Agencies, Ranks, Divisions & Certifications API
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\RankController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\CertificationController;

Route::middleware(['auth', 'admin'])->prefix('api/v1/agencies')->group(function () {
    Route::get('/', [AgencyController::class, 'index']);
    Route::post('/', [AgencyController::class, 'store']);
    Route::put('/{id}', [AgencyController::class, 'update']);
    Route::delete('/{id}', [AgencyController::class, 'destroy']);
});

Route::middleware(['auth', 'admin'])->prefix('api/v1/ranks')->group(function () {
    Route::get('/', [RankController::class, 'index']);
    Route::post('/', [RankController::class, 'store']);
    Route::put('/{id}', [RankController::class, 'update']);
    Route::delete('/{id}', [RankController::class, 'destroy']);
});

Route::middleware(['auth', 'admin'])->prefix('api/v1/divisions')->group(function () {
    Route::get('/', [DivisionController::class, 'index']);
    Route::post('/', [DivisionController::class, 'store']);
    Route::put('/{id}', [DivisionController::class, 'update']);
    Route::delete('/{id}', [DivisionController::class, 'destroy']);
});

Route::middleware(['auth', 'admin'])->prefix('api/v1/certifications')->group(function () {
    Route::get('/', [CertificationController::class, 'index']);
    Route::post('/', [CertificationController::class, 'store']);
    Route::delete('/{id}', [CertificationController::class, 'destroy']);
});

// Admin-Protected User Accounts Management API
use App\Http\Controllers\UserManagementController;
Route::middleware(['auth', 'admin'])->prefix('api/v1/admin/users')->group(function () {
    Route::get('/', [UserManagementController::class, 'index']);
    Route::delete('/{id}', [UserManagementController::class, 'destroy']);
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
