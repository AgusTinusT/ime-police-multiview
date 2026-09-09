<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PoliceCommandController;
use App\Http\Controllers\ProfileController;
use Inertia\Inertia;

// Main Police Tactical Command Center Dashboard
Route::get('/', [PoliceCommandController::class, 'dashboard'])->name('home');
Route::get('/dashboard', [PoliceCommandController::class, 'dashboard'])->name('dashboard');

// REST API Endpoints
Route::prefix('api/v1')->group(function () {
    Route::get('/streams', [PoliceCommandController::class, 'apiStreams']);
    Route::post('/sync', [PoliceCommandController::class, 'apiSync']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
