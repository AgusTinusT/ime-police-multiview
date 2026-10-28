<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LinkDomainDenylist;
use App\Models\OfficerBioLink;
use App\Models\OfficerBioProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminBioModerationController extends Controller
{
    /**
     * Admin: List all bio links with search and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = OfficerBioLink::with([
            'profile.officer',
            'recommendedOfficer',
            'moderatedBy',
        ])->latest();

        if ($request->filled('moderation_status')) {
            $query->where('moderation_status', $request->moderation_status);
        }

        if ($request->filled('link_type')) {
            $query->where('link_type', $request->link_type);
        }

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', $s)
                    ->orWhere('url', 'like', $s)
                    ->orWhere('url_host', 'like', $s)
                    ->orWhereHas('profile.officer', function ($oq) use ($s) {
                        $oq->where('officer_name', 'like', $s)
                            ->orWhere('streamer_name', 'like', $s);
                    });
            });
        }

        return response()->json([
            'links' => $query->paginate(25),
        ]);
    }

    /**
     * Admin: Moderate a single link (approve, flag, or ban).
     */
    public function moderate(Request $request, OfficerBioLink $link): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['approved', 'flagged', 'banned'])],
            'reason' => 'required|string|min:3|max:255',
            'ban_domain' => 'boolean',
        ]);

        $beforeState = [
            'moderation_status' => $link->moderation_status,
            'moderation_reason' => $link->moderation_reason,
        ];

        $link->moderation_status = $request->status;
        $link->moderation_reason = $request->reason;
        $link->moderated_by = Auth::id();
        $link->moderated_at = now();

        if ($request->status === 'banned') {
            $link->is_active = false;
        }

        $link->save();

        // Optionally ban domain host globally
        if ($request->boolean('ban_domain') && !empty($link->url_host)) {
            LinkDomainDenylist::firstOrCreate(
                ['host' => $link->url_host],
                [
                    'reason' => "Global domain ban via moderation on link #{$link->id}: {$request->reason}",
                    'added_by' => Auth::id(),
                    'created_at' => now(),
                ]
            );
        }

        AuditLogger::log(
            'bio_link.moderated',
            'officer_bio_link',
            $link->id,
            $beforeState,
            ['status' => $request->status, 'reason' => $request->reason],
            $request->reason
        );

        return response()->json([
            'message' => "Status moderasi tautan diubah menjadi '{$request->status}'.",
            'link' => $link->load('moderatedBy'),
        ]);
    }

    /**
     * Admin: Force delete a bio link.
     */
    public function destroy(Request $request, OfficerBioLink $link): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $title = $link->title;
        $profileId = $link->officer_bio_profile_id;

        $link->delete();

        AuditLogger::log(
            'bio_link.force_deleted',
            'officer_bio_link',
            $link->id,
            ['title' => $title, 'profile_id' => $profileId],
            null,
            $request->reason ?? 'Dihapus paksa oleh Admin SASP'
        );

        return response()->json(['message' => 'Tautan berhasil dihapus permanen oleh Admin.']);
    }

    /**
     * Admin: Ban a domain globally.
     */
    public function banDomain(Request $request): JsonResponse
    {
        $request->validate([
            'host' => 'required|string|max:190',
            'reason' => 'required|string|min:3|max:255',
        ]);

        $host = strtolower(trim($request->host));
        $host = preg_replace('/^https?:\/\//', '', $host);
        $host = preg_replace('/\/.*$/', '', $host);
        $host = preg_replace('/^www\./', '', $host);

        $denylist = LinkDomainDenylist::updateOrCreate(
            ['host' => $host],
            [
                'reason' => $request->reason,
                'added_by' => Auth::id(),
                'created_at' => now(),
            ]
        );

        // Deactivate all existing links using this host or subdomains (B-06)
        $deactivatedCount = OfficerBioLink::where(function ($q) use ($host) {
            $q->where('url_host', $host)
              ->orWhere('url_host', 'like', '%.' . $host);
        })->update([
            'moderation_status' => 'banned',
            'moderation_reason' => "Domain global ban: {$request->reason}",
            'is_active' => false,
        ]);

        AuditLogger::log(
            'domain.banned',
            'link_domain_denylist',
            $denylist->id,
            null,
            ['host' => $host, 'deactivated_links' => $deactivatedCount],
            $request->reason
        );

        return response()->json([
            'message' => "Domain '{$host}' telah dimasukkan ke denylist global. {$deactivatedCount} tautan dinonaktifkan.",
            'denylist' => $denylist,
        ]);
    }

    /**
     * Admin: Kill-switch toggle for an officer bio-link profile (Level 2).
     */
    public function suspendProfile(Request $request, OfficerBioProfile $profile): JsonResponse
    {
        $request->validate([
            'is_suspended' => 'required|boolean',
            'reason' => 'required|string|min:3|max:255',
        ]);

        $beforeState = [
            'is_suspended' => $profile->is_suspended,
            'suspension_reason' => $profile->suspension_reason,
        ];

        $isSuspended = $request->boolean('is_suspended');
        $profile->is_suspended = $isSuspended;
        $profile->suspension_reason = $isSuspended ? $request->reason : null;
        $profile->suspended_by = $isSuspended ? Auth::id() : null;
        $profile->suspended_at = $isSuspended ? now() : null;
        $profile->save();

        AuditLogger::log(
            $isSuspended ? 'bio_profile.suspended' : 'bio_profile.unsuspended',
            'officer_bio_profile',
            $profile->id,
            $beforeState,
            ['is_suspended' => $isSuspended, 'reason' => $request->reason],
            $request->reason
        );

        return response()->json([
            'message' => $isSuspended
                ? "Kill-switch diaktifkan: Halaman bio {$profile->slug} telah dibekukan."
                : "Pembekuan halaman bio {$profile->slug} telah dicabut.",
            'profile' => $profile,
        ]);
    }

    /**
     * Admin: Ban or unban user account (Level 4).
     */
    public function setUserStatus(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['active', 'banned'])],
            'reason' => 'required_if:status,banned|nullable|string|max:255',
        ]);

        $beforeState = [
            'status' => $user->status,
            'banned_reason' => $user->banned_reason,
        ];

        $user->status = $request->status;
        $user->banned_reason = $request->status === 'banned' ? $request->reason : null;
        $user->banned_at = $request->status === 'banned' ? now() : null;
        $user->save();

        // If banned, kill all active sessions of this user
        if ($user->status === 'banned') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        AuditLogger::log(
            $user->status === 'banned' ? 'user.banned' : 'user.unbanned',
            'user',
            $user->id,
            $beforeState,
            ['status' => $user->status, 'reason' => $request->reason],
            $request->reason
        );

        return response()->json([
            'message' => $user->status === 'banned'
                ? "Akun {$user->name} berhasil di-ban dan seluruh sesi aktif diputus."
                : "Akun {$user->name} telah dipulihkan (Active).",
            'user' => $user,
        ]);
    }

    /**
     * Admin: Paginated audit trail viewer.
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $query = AuditLog::with('actor')->latest();

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        return response()->json([
            'logs' => $query->paginate(30),
        ]);
    }
}
