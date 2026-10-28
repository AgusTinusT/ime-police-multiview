<?php

namespace App\Http\Controllers;

use App\Models\ClipperRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ClipperRequestController extends Controller
{
    /**
     * Get the current user's latest request status.
     */
    public function userStatus(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => null]);
        }

        $latestRequest = ClipperRequest::where('user_id', $user->id)
            ->latest()
            ->first();

        return response()->json([
            'status' => $latestRequest ? $latestRequest->status : null,
            'request' => $latestRequest,
        ]);
    }

    /**
     * Submit a new request for Clipper role.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->canTrimVideo()) {
            return response()->json([
                'message' => 'Akun Anda sudah memiliki akses Clipper / Admin.'
            ], 400);
        }

        $existingPending = ClipperRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return response()->json([
                'message' => 'Anda sudah memiliki pengajuan akses Clipper yang sedang diproses.'
            ], 422);
        }

        $validated = $request->validate([
            'request_type' => 'required|string|in:officer,creator,vip,general',
            'channel_url' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:1000',
        ]);

        $clipperRequest = ClipperRequest::create([
            'user_id' => $user->id,
            'request_type' => $validated['request_type'],
            'channel_url' => $validated['channel_url'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        // Send Discord Webhook Notification
        $this->sendDiscordNotification($user, $clipperRequest);

        return response()->json([
            'message' => 'Pengajuan hak spesial Clipper berhasil dikirim dan menunggu review Admin.',
            'request' => $clipperRequest,
        ], 201);
    }

    /**
     * Send rich Discord Webhook Notification for new Clipper Requests.
     */
    private function sendDiscordNotification(User $user, ClipperRequest $clipperRequest)
    {
        $webhookUrl = config('services.discord.webhook_url');

        if (empty($webhookUrl)) {
            return;
        }

        try {
            $typeName = match ($clipperRequest->request_type) {
                'officer' => '🛡️ Police Officer Duty (Slot Officer)',
                'vip' => '💎 VIP Supporter (Unlimited)',
                'creator' => '⚡ Clipper Komunitas IME (10 Klip/Hari)',
                default => '🎬 Clipper General Request',
            };

            $fields = [
                [
                    'name' => '👤 User Pengaju',
                    'value' => "**{$user->name}**\n`{$user->email}`",
                    'inline' => true,
                ],
                [
                    'name' => '🎯 Tipe Pengajuan',
                    'value' => "**{$typeName}**",
                    'inline' => true,
                ],
            ];

            if (!empty($clipperRequest->channel_url)) {
                $fields[] = [
                    'name' => '📺 Link Channel YouTube',
                    'value' => $clipperRequest->channel_url,
                    'inline' => false,
                ];
            }

            if (!empty($clipperRequest->reason)) {
                $fields[] = [
                    'name' => '📝 Catatan / Detail Bukti Pengajuan',
                    'value' => mb_strimwidth($clipperRequest->reason, 0, 1000, '...'),
                    'inline' => false,
                ];
            }

            $payload = [
                'username' => 'IME Police Clipper Bot',
                'avatar_url' => 'https://img.icons8.com/fluency/96/video-trimmer.png',
                'embeds' => [
                    [
                        'title' => '📥 Pengajuan Baru: Hak Akses Tactical Clipper',
                        'description' => "Pengguna **{$user->name}** baru saja mengirimkan form pengajuan hak akses Clipper.",
                        'color' => 3447003, // Royal Blue
                        'fields' => $fields,
                        'footer' => [
                            'text' => 'IME Police Multiview System • Automatic Notification',
                        ],
                        'timestamp' => now()->toISOString(),
                    ]
                ]
            ];

            Http::timeout(5)->post($webhookUrl, $payload);
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim Discord Webhook notification: ' . $e->getMessage());
        }
    }

    /**
     * Admin: List all clipper requests.
     */
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $requests = ClipperRequest::with('user:id,name,email,role,clipper_tier')
            ->latest()
            ->paginate(20);

        return response()->json($requests);
    }

    /**
     * Admin: Approve a clipper request and grant clipper role to user.
     */
    public function approve(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $clipperReq = ClipperRequest::findOrFail($id);
        $clipperReq->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        // Promote user role to 'clipper' (only if not admin and not officer) and assign corresponding tier
        $user = User::findOrFail($clipperReq->user_id);
        if ($user->role !== 'admin' && !$user->isOfficer()) {
            $user->role = 'clipper';
        }
        $user->clipper_tier = ($clipperReq->request_type === 'creator') ? 'creator' : 'ultimate';
        $user->save();

        return response()->json([
            'message' => "Pengajuan dari {$user->name} berhasil disetujui ({$user->getClipperTierName()}).",
            'request' => $clipperReq,
        ]);
    }

    /**
     * Admin: Reject a clipper request.
     */
    public function reject(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $clipperReq = ClipperRequest::findOrFail($id);
        $clipperReq->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Pengajuan akses Clipper telah ditolak.',
            'request' => $clipperReq,
        ]);
    }
}
