<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PoliceCommandController;
use App\Http\Controllers\OfficerManagementController;
use App\Http\Controllers\ProfileController;
use Inertia\Inertia;

// Main Police Tactical Command Center Dashboard
Route::get('/', [PoliceCommandController::class, 'dashboard'])->name('home');
Route::get('/dashboard', [PoliceCommandController::class, 'dashboard'])->name('dashboard');

// Admin direct slash route (hidden access)
Route::get('/admin', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('admin');

// Redirect any attempt to access /register to home
Route::get('/register', function () {
    return redirect()->route('home');
});

// REST API Endpoints (Public Stream Telemetry)
Route::prefix('api/v1')->group(function () {
    Route::get('/streams', [PoliceCommandController::class, 'apiStreams']);
    Route::post('/sync', [PoliceCommandController::class, 'apiSync']);
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
