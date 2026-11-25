<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PushSubscriptionController extends Controller
{
    public function config(Request $request, WebPushService $webPush): JsonResponse
    {
        return response()->json([
            'enabled' => $webPush->isConfigured(),
            'public_key' => $webPush->publicKey(),
            'subscriptions_count' => $request->user()->pushSubscriptions()->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:2048'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:512'],
            'keys.auth' => ['required', 'string', 'max:256'],
            'content_encoding' => ['sometimes', 'string', 'in:aes128gcm,aesgcm'],
        ]);

        $endpoint = trim($validated['endpoint']);
        $hash = PushSubscription::hashEndpoint($endpoint);

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint_hash' => $hash],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $endpoint,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['content_encoding'] ?? 'aes128gcm',
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
                'last_used_at' => now(),
            ],
        );

        return response()->json([
            'message' => 'Notifikasi browser berhasil diaktifkan pada perangkat ini.',
            'id' => $subscription->id,
            'subscriptions_count' => $request->user()->pushSubscriptions()->count(),
        ], $subscription->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
        ]);

        $hash = PushSubscription::hashEndpoint($validated['endpoint']);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint_hash', $hash)
            ->delete();

        return response()->json([
            'message' => 'Notifikasi browser dinonaktifkan pada perangkat ini.',
            'subscriptions_count' => $request->user()->pushSubscriptions()->count(),
        ]);
    }

    public function test(Request $request, WebPushService $webPush): JsonResponse
    {
        if (! $webPush->isConfigured()) {
            return response()->json([
                'message' => 'Web Push belum dikonfigurasi pada server (VAPID key kosong).',
            ], 422);
        }

        $user = $request->user()->load('pushSubscriptions');
        if ($user->pushSubscriptions->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada perangkat aktif yang berlangganan notifikasi browser.',
            ], 422);
        }

        $result = $webPush->sendTestNotification($user);

        return response()->json([
            'message' => $result['sent'] > 0
                ? 'Notifikasi uji coba berhasil dikirim.'
                : 'Gagal mengirim notifikasi uji coba ke perangkat aktif.',
            'result' => $result,
        ], $result['sent'] > 0 ? 200 : 422);
    }
}
