<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DiscordNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DiscordAccountController extends Controller
{
    public function status(Request $request, DiscordNotificationService $discord): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'oauth_enabled' => $discord->isOAuthConfigured(),
            'bot_enabled' => $discord->isBotConfigured(),
            'channel_enabled' => $discord->isChannelConfigured(),
            'invite_url' => config('support.discord_url'),
            'connected' => $user->hasDiscordConnected(),
            'discord_id' => $user->discord_id,
            'discord_username' => $user->discord_username,
            'discord_avatar_url' => $user->discord_avatar_url,
            'discord_connected_at' => $user->discord_connected_at?->toIso8601String(),
            'dm_failed_reason' => $user->discord_dm_failed_reason,
        ]);
    }

    public function redirect(Request $request, DiscordNotificationService $discord): RedirectResponse
    {
        if (! $discord->isOAuthConfigured()) {
            return redirect('/settings?tab=notifications&discord=unconfigured');
        }

        $state = Str::random(40);
        $request->session()->put('discord_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.discord.client_id'),
            'redirect_uri' => config('services.discord.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'identify',
            'state' => $state,
            'prompt' => 'consent',
        ]);

        return redirect()->away('https://discord.com/oauth2/authorize?' . $query);
    }

    public function callback(Request $request, DiscordNotificationService $discord): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect('/settings?tab=notifications&discord=cancelled');
        }

        $expectedState = (string) $request->session()->pull('discord_oauth_state', '');
        $actualState = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        if ($expectedState === '' || ! hash_equals($expectedState, $actualState) || $code === '') {
            return redirect('/settings?tab=notifications&discord=invalid_state');
        }

        if (! $discord->isOAuthConfigured()) {
            return redirect('/settings?tab=notifications&discord=unconfigured');
        }

        try {
            $tokenResponse = Http::asForm()->timeout(10)->post('https://discord.com/api/oauth2/token', [
                'client_id' => config('services.discord.client_id'),
                'client_secret' => config('services.discord.client_secret'),
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('services.discord.redirect_uri'),
            ]);

            if (! $tokenResponse->successful() || empty($tokenResponse->json('access_token'))) {
                Log::warning('Discord OAuth token exchange failed', ['status' => $tokenResponse->status()]);

                return redirect('/settings?tab=notifications&discord=error');
            }

            $userResponse = Http::timeout(10)
                ->withToken((string) $tokenResponse->json('access_token'))
                ->get('https://discord.com/api/v10/users/@me');

            if (! $userResponse->successful() || empty($userResponse->json('id'))) {
                Log::warning('Discord OAuth profile fetch failed', ['status' => $userResponse->status()]);

                return redirect('/settings?tab=notifications&discord=error');
            }

            $discordId = (string) $userResponse->json('id');
            $username = (string) ($userResponse->json('global_name') ?: $userResponse->json('username') ?: $discordId);
            $avatar = $userResponse->json('avatar');

            $user = $request->user();
            $alreadyUsed = User::where('discord_id', $discordId)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($alreadyUsed) {
                return redirect('/settings?tab=notifications&discord=conflict');
            }

            $user->forceFill([
                'discord_id' => $discordId,
                'discord_username' => $username,
                'discord_avatar' => is_string($avatar) ? $avatar : null,
                'discord_dm_channel_id' => null,
                'discord_connected_at' => now(),
                'discord_dm_failed_reason' => null,
            ])->save();

            return redirect('/settings?tab=notifications&discord=connected');
        } catch (Throwable $e) {
            Log::warning('Discord OAuth callback exception: ' . $e->getMessage());

            return redirect('/settings?tab=notifications&discord=error');
        }
    }

    public function testDm(Request $request, DiscordNotificationService $discord): JsonResponse
    {
        $user = $request->user();

        if (! $discord->isBotConfigured()) {
            return response()->json([
                'message' => 'Discord Bot belum dikonfigurasi pada server.',
            ], 503);
        }

        if (! $user->hasDiscordConnected()) {
            return response()->json([
                'message' => 'Akun Discord belum terhubung. Silakan hubungkan terlebih dahulu.',
            ], 422);
        }

        $result = $discord->sendTestDm($user);
        if (! $result['sent']) {
            return response()->json([
                'message' => $result['message'] ?: 'Gagal mengirim DM uji coba.',
                'error_code' => $result['error_code'],
            ], 422);
        }

        return response()->json([
            'message' => 'DM uji coba berhasil dikirim ke Discord kamu!',
        ]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $request->user()->forceFill([
            'discord_id' => null,
            'discord_username' => null,
            'discord_avatar' => null,
            'discord_dm_channel_id' => null,
            'discord_connected_at' => null,
            'discord_dm_failed_reason' => null,
        ])->save();

        return response()->json([
            'message' => 'Tautan akun Discord berhasil diputuskan.',
        ]);
    }
}
