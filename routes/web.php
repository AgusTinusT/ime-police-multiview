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
Route::get('/multiview', [PoliceCommandController::class, 'multiview'])->name('multiview');

// Dedicated Standalone Pages
Route::get('/officers', [PoliceCommandController::class, 'officers'])->name('officers.index');
Route::get('/action-clips', [PoliceCommandController::class, 'clipsPage'])->name('clips');
Route::get('/clips', function () {
    return redirect()->route('clips');
});
Route::get('/about', [PoliceCommandController::class, 'about'])->name('about');
Route::get('/faq', [PoliceCommandController::class, 'faqPage'])->name('faq');
Route::get('/qna', function () {
    return redirect()->route('faq');
})->name('qna');
Route::get('/updates', [PoliceCommandController::class, 'updatesPage'])->name('updates');
Route::get('/changelog', function () {
    return redirect()->route('updates');
})->name('changelog');
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
    Route::get('/streams', [PoliceCommandController::class, 'apiStreams'])->middleware('throttle:60,1');
    Route::post('/sync', [PoliceCommandController::class, 'apiSync'])->middleware('throttle:60,1');
    Route::match(['get', 'post'], '/search-live', [PoliceCommandController::class, 'apiSearchLive'])->middleware('throttle:60,1');
    Route::get('/stream-details', [PoliceCommandController::class, 'apiStreamDetails'])->middleware('throttle:60,1');
    Route::match(['get', 'post'], '/telemetry', [PoliceCommandController::class, 'apiTelemetry'])->middleware('throttle:60,1');
    Route::post('/feedback', [FeedbackController::class, 'submit'])->middleware('throttle:5,1');
    
    // TacClips Community Momen Aksi API
    Route::prefix('tac-clips')->group(function () {
        Route::get('/', [PoliceCommandController::class, 'getTacClips']);
        Route::post('/', [PoliceCommandController::class, 'storeTacClip']);
        Route::post('/{id}/like', [PoliceCommandController::class, 'likeTacClip']);
        Route::delete('/{id}', [PoliceCommandController::class, 'destroyTacClip']);
    });

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
    Route::patch('/{id}/adjust-duty', [OfficerManagementController::class, 'adjustDuty']);
    Route::patch('/{id}/toggle-hashtag-bypass', [OfficerManagementController::class, 'toggleHashtagBypass']);
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
use App\Http\Controllers\VideoClipController;

Route::middleware('auth')->prefix('api/v1/user/watchlist')->group(function () {
    Route::get('/', [UserWatchlistController::class, 'index']);
    Route::post('/toggle', [UserWatchlistController::class, 'toggle']);
});

use App\Http\Controllers\ClipperRequestController;

// Dedicated Tactical Video Trimmer Workspace Page (Public Teaser & Auth Workspace)
Route::get('/clipper', [VideoClipController::class, 'clipperPage'])->name('clipper');
Route::get('/clipper/pricing', function () {
    return redirect()->route('clipper');
})->name('clipper.pricing');
Route::get('/clipper/police-guide', function () {
    return redirect()->route('clipper');
})->name('clipper.police-guide');
Route::get('/police-clipper', function () {
    return redirect()->route('clipper');
});
Route::get('/pricing', function () {
    return redirect()->route('clipper');
})->name('pricing');

// Public YouTube URL Checker (For Teaser & Interactive Preview)
Route::post('/api/v1/clips/check-url', [VideoClipController::class, 'checkUrl'])->middleware('throttle:30,1');

// Authenticated Video Trimming & Clip Management API
Route::middleware('auth')->prefix('api/v1/clips')->group(function () {
    Route::get('/', [VideoClipController::class, 'index']);
    Route::post('/trim', [VideoClipController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/{id}/download', [VideoClipController::class, 'download'])->name('clips.download');
    Route::delete('/{id}', [VideoClipController::class, 'destroy']);
});

// Authenticated Clipper Role Request API
Route::middleware('auth')->prefix('api/v1/clipper-requests')->group(function () {
    Route::get('/status', [ClipperRequestController::class, 'userStatus']);
    Route::post('/', [ClipperRequestController::class, 'store'])->middleware('throttle:5,1');
    
    // Admin Review Endpoints
    Route::middleware('admin')->group(function () {
        Route::get('/admin', [ClipperRequestController::class, 'index']);
        Route::post('/admin/{id}/approve', [ClipperRequestController::class, 'approve']);
        Route::post('/admin/{id}/reject', [ClipperRequestController::class, 'reject']);
    });
});

use App\Http\Controllers\ClipperDelegationController;

// Officer / Clipper Delegation API (2-Slot Assignment & Revocation)
Route::middleware('auth')->prefix('api/v1/clipper-delegations')->group(function () {
    Route::get('/', [ClipperDelegationController::class, 'index']);
    Route::post('/assign', [ClipperDelegationController::class, 'assign'])->middleware('throttle:10,1');
    Route::delete('/{id}', [ClipperDelegationController::class, 'revoke']);
});


// Tactical Community Group Chat API
use App\Http\Controllers\ChatMessageController;
Route::get('/api/v1/chat/messages', [ChatMessageController::class, 'index']);
Route::middleware('auth')->prefix('api/v1/chat/messages')->group(function () {
    Route::post('/', [ChatMessageController::class, 'store']);
});
Route::middleware(['auth', 'admin'])->prefix('api/v1/chat/messages')->group(function () {
    Route::delete('/{id}', [ChatMessageController::class, 'destroy']);
    Route::patch('/{id}/pin', [ChatMessageController::class, 'togglePin']);
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
    Route::post('/', [UserManagementController::class, 'store']);
    Route::put('/{id}', [UserManagementController::class, 'update']);
    Route::delete('/{id}', [UserManagementController::class, 'destroy']);
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// ==========================================
// OFFICER TACTICAL BIO-LINK & ANTI-IMPERSONATION HUB
// ==========================================
use App\Http\Controllers\OfficerBioLinkController;
use App\Http\Controllers\OfficerBindingController;
use App\Http\Controllers\OfficerClaimController;
use App\Http\Controllers\AdminBioModerationController;
use App\Http\Controllers\PublicBioLinkController;

// 1. Authenticated Officer Dashboard & API
Route::middleware(['auth', 'verified', 'officer', 'not_banned'])->group(function () {
    Route::get('/dashboard/bio-links', [OfficerBioLinkController::class, 'dashboard'])->name('officer.bio-links');
    
    Route::prefix('api/v1/officer')->group(function () {
        Route::post('/bio-links', [OfficerBioLinkController::class, 'store'])->middleware('throttle:30,1');
        Route::put('/bio-links/{link}', [OfficerBioLinkController::class, 'update'])->middleware('throttle:30,1');
        Route::patch('/bio-links/{link}/toggle', [OfficerBioLinkController::class, 'toggle']);
        Route::post('/bio-links/reorder', [OfficerBioLinkController::class, 'reorder']);
        Route::delete('/bio-links/{link}', [OfficerBioLinkController::class, 'destroy']);
        Route::put('/bio-profile', [OfficerBioLinkController::class, 'updateProfile']);
        Route::get('/bio-links/history', [OfficerBioLinkController::class, 'history']);
    });
});

// 2. Member Officer Claim Requests
Route::middleware(['auth', 'verified', 'not_banned'])->prefix('api/v1/officer-claims')->group(function () {
    Route::get('/mine', [OfficerClaimController::class, 'myClaims']);
    Route::post('/', [OfficerClaimController::class, 'store'])->middleware('throttle:5,60');
    Route::delete('/{claim}', [OfficerClaimController::class, 'destroy']);
});

Route::middleware(['auth'])->get('/api/v1/officers/unclaimed', [OfficerClaimController::class, 'unclaimedOfficers']);

// 3. Admin Moderation, Binding & Claim Review
Route::middleware(['auth', 'admin'])->prefix('api/v1/admin')->group(function () {
    // Claims Review
    Route::get('/officer-claims', [OfficerClaimController::class, 'index']);
    Route::post('/officer-claims/{claim}/approve', [OfficerClaimController::class, 'approve']);
    Route::post('/officer-claims/{claim}/reject', [OfficerClaimController::class, 'reject']);

    // 1-to-1 Bindings
    Route::post('/officers/{officer}/bind-user', [OfficerBindingController::class, 'bindUser']);
    Route::post('/officers/{officer}/unbind-user', [OfficerBindingController::class, 'unbindUser']);
    Route::patch('/bio-profiles/{profile}/slug', [OfficerBindingController::class, 'updateSlug']);

    // Moderation & Kill-Switch
    Route::get('/bio-links', [AdminBioModerationController::class, 'index']);
    Route::patch('/bio-links/{link}/moderate', [AdminBioModerationController::class, 'moderate']);
    Route::delete('/bio-links/{link}', [AdminBioModerationController::class, 'destroy']);
    Route::post('/link-denylist', [AdminBioModerationController::class, 'banDomain']);
    Route::patch('/bio-profiles/{profile}/suspend', [AdminBioModerationController::class, 'suspendProfile']);
    Route::patch('/users/{user}/status', [AdminBioModerationController::class, 'setUserStatus']);
    Route::get('/audit-logs', [AdminBioModerationController::class, 'auditLogs']);
});

// 4. Public Safe Redirect (/go/{link})
Route::get('/go/{link}', [PublicBioLinkController::class, 'handleClick'])->middleware('throttle:60,1')->name('bio.go');

// 5. Legacy / Convenience 301 Redirect (/links/{slug} -> /@{slug})
Route::get('/links/{slug}', function (string $slug) {
    return redirect()->to('/@' . $slug, 301);
});

// 6. Public Officer Tactical Bio-Link Page (Registered last to prevent route shadow)
Route::get('/@{slug}', [PublicBioLinkController::class, 'show'])
    ->where('slug', '[a-zA-Z0-9._-]+')
    ->name('bio.show');

